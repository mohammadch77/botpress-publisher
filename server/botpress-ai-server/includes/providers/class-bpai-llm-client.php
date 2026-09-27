<?php

defined('ABSPATH') || exit;

/**
 * OpenAI-compatible chat client. ArvanCloud's AI Gateway uses this protocol with
 * an "Authorization: apikey <key>" header; OpenRouter/OpenAI use "Bearer".
 */
class BPAI_LLM_Client {
    private string $base_url;
    private string $api_key;
    private string $auth_scheme;
    private int $timeout;

    public function __construct(?string $base_url = null, ?string $api_key = null, ?string $auth_scheme = null, ?int $timeout = null) {
        $this->base_url    = rtrim($base_url ?? (string) BPAI_Settings::get('llm_base_url'), '/');
        $this->api_key     = $api_key ?? BPAI_Settings::llm_api_key();
        $this->auth_scheme = $auth_scheme ?? (string) BPAI_Settings::get('llm_auth_scheme', 'apikey');
        $this->timeout     = $timeout ?? (int) BPAI_Settings::get('llm_timeout', 120);
    }

    public function is_configured(): bool {
        return $this->base_url !== '' && $this->api_key !== '';
    }

    private function headers(): array {
        $scheme = $this->auth_scheme === 'bearer' ? 'Bearer' : 'apikey';
        return [
            'Authorization' => $scheme . ' ' . $this->api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * @param array $messages [['role' => 'system'|'user'|'assistant', 'content' => string], ...]
     * @param array $context  Usage-log fields: stage, license_id, job_ref.
     * @return array{content: string, input_tokens: int, output_tokens: int, cost_toman: float, raw: array}|WP_Error
     */
    public function chat(string $model, array $messages, array $options = [], array $context = []) {
        if (!$this->is_configured()) {
            return new WP_Error('bpai_llm_not_configured', 'آدرس یا کلید API مدل زبانی تنظیم نشده است.');
        }

        $body = array_filter([
            'model'           => $model,
            'messages'        => $messages,
            'max_tokens'      => $options['max_tokens'] ?? 4000,
            'temperature'     => $options['temperature'] ?? null,
            'response_format' => !empty($options['json']) ? ['type' => 'json_object'] : null,
        ], static fn($v) => $v !== null);

        $started = microtime(true);
        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'headers' => $this->headers(),
            'body'    => wp_json_encode($body),
            'timeout' => $this->timeout,
        ]);
        $duration = (int) round((microtime(true) - $started) * 1000);

        $result = $this->parse($response);
        $usage_row = [
            'license_id'  => $context['license_id'] ?? null,
            'job_ref'     => $context['job_ref'] ?? '',
            'stage'       => $context['stage'] ?? 'test',
            'provider'    => 'llm',
            'model'       => $model,
            'duration_ms' => $duration,
        ];

        if (is_wp_error($result)) {
            BPAI_Usage::record($usage_row + ['success' => 0, 'error' => $result->get_error_message(), 'cost_toman' => 0]);
            return $result;
        }

        $input  = (int) ($result['usage']['prompt_tokens'] ?? 0);
        $output = (int) ($result['usage']['completion_tokens'] ?? 0);
        $cost   = BPAI_Usage::cost($model, $input, $output);
        BPAI_Usage::record($usage_row + ['input_tokens' => $input, 'output_tokens' => $output, 'cost_toman' => $cost]);

        return [
            'content'       => (string) ($result['choices'][0]['message']['content'] ?? ''),
            'input_tokens'  => $input,
            'output_tokens' => $output,
            'cost_toman'    => $cost,
            'raw'           => $result,
        ];
    }

    /** @return string[]|WP_Error Model ids visible to this key. */
    public function list_models() {
        if (!$this->is_configured()) {
            return new WP_Error('bpai_llm_not_configured', 'آدرس یا کلید API مدل زبانی تنظیم نشده است.');
        }
        $response = wp_remote_get($this->base_url . '/models', ['headers' => $this->headers(), 'timeout' => 20]);
        $result = $this->parse($response);
        if (is_wp_error($result)) {
            return $result;
        }
        $ids = array_map(static fn($m) => (string) ($m['id'] ?? ''), (array) ($result['data'] ?? []));
        return array_values(array_filter($ids));
    }

    /** @return array|WP_Error */
    private function parse($response) {
        if (is_wp_error($response)) {
            return new WP_Error('bpai_llm_http', 'اتصال به سرویس مدل زبانی برقرار نشد: ' . $response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code >= 200 && $code < 300 && is_array($data)) {
            return $data;
        }
        $message = is_array($data) ? ($data['error']['message'] ?? $data['message'] ?? '') : '';
        $hints = [
            401 => 'کلید API نامعتبر است یا در هدر درست ارسال نشده.',
            403 => 'این کلید به مدل یا درگاه موردنظر دسترسی ندارد.',
            404 => 'آدرس درگاه یا نام مدل اشتباه است.',
            429 => 'از محدودیت تعداد درخواست (Rate Limit) عبور کرده‌اید.',
        ];
        $text = $hints[$code] ?? 'پاسخ نامعتبر از سرویس مدل زبانی.';
        return new WP_Error('bpai_llm_' . $code, trim("خطای {$code}: {$text} {$message}"));
    }
}
