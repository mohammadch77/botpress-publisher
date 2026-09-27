<?php

defined('ABSPATH') || exit;

/**
 * Article-generation settings on the customer site. In "server" mode only a license key is stored
 * and all AI work happens on the central BotPress AI server; "byok" talks to the customer's own gateway.
 */
class BotPress_AI_Settings {
    private const OPTION = 'botpress_ai_settings';

    /** Default central server; customers normally never change it. */
    public const DEFAULT_SERVER_URL = 'https://foladabzar.com';

    public static function defaults(): array {
        return [
            'mode'            => 'server',
            'server_url'      => self::DEFAULT_SERVER_URL,
            'license_key_enc' => '',
            'byok_base_url'   => '',
            'byok_auth'       => 'apikey',
            'byok_key_enc'    => '',
            'byok_model'      => 'Claude-Sonnet-4.6',
        ];
    }

    public static function all(): array {
        $stored = get_option(self::OPTION, []);
        return array_replace(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function update(array $values): void {
        update_option(self::OPTION, array_replace(self::all(), $values), false);
    }

    public static function license_key(): string {
        return BotPress_Encryption::decrypt((string) self::all()['license_key_enc']);
    }

    public static function byok_key(): string {
        return BotPress_Encryption::decrypt((string) self::all()['byok_key_enc']);
    }

    /** Safe representation for the admin UI — secrets are masked. */
    public static function public_view(): array {
        $s = self::all();
        $license = self::license_key();
        $byok = self::byok_key();
        return [
            'mode'           => $s['mode'],
            'server_url'     => $s['server_url'],
            'has_license'    => $license !== '',
            'license_masked' => $license !== '' ? BotPress_Encryption::mask($license) : '',
            'byok_base_url'  => $s['byok_base_url'],
            'byok_auth'      => $s['byok_auth'],
            'has_byok_key'   => $byok !== '',
            'byok_key_masked' => $byok !== '' ? BotPress_Encryption::mask($byok) : '',
            'byok_model'     => $s['byok_model'],
        ];
    }

    /**
     * Server mode: fetches account info (credits, status) from the central server.
     *
     * @return array|WP_Error
     */
    public static function server_account() {
        $s = self::all();
        $license = self::license_key();
        if ($license === '') {
            return new WP_Error('botpress_ai_no_license', 'کلید لایسنس وارد نشده است.');
        }
        $response = wp_remote_get(rtrim($s['server_url'], '/') . '/wp-json/bpai/v1/account', [
            'timeout' => 20,
            'headers' => [
                'X-BPAI-License' => $license,
                'X-BPAI-Site'    => home_url(),
                'Accept'         => 'application/json',
            ],
        ]);
        if (is_wp_error($response)) {
            return new WP_Error('botpress_ai_server_unreachable', 'اتصال به سرور هوش مصنوعی برقرار نشد: ' . $response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code !== 200 || !is_array($data)) {
            $message = is_array($data) && !empty($data['message']) ? $data['message'] : "پاسخ نامعتبر از سرور (کد {$code}).";
            return new WP_Error('botpress_ai_server_error', $message);
        }
        return $data;
    }

    /**
     * BYOK mode: sends a tiny chat request to the customer's own OpenAI-compatible gateway.
     *
     * @return array|WP_Error
     */
    public static function test_byok() {
        $s = self::all();
        $key = self::byok_key();
        if ($s['byok_base_url'] === '' || $key === '') {
            return new WP_Error('botpress_ai_byok_missing', 'آدرس درگاه و کلید API را وارد و ذخیره کنید.');
        }
        $scheme = $s['byok_auth'] === 'bearer' ? 'Bearer' : 'apikey';
        $started = microtime(true);
        $response = wp_remote_post(rtrim($s['byok_base_url'], '/') . '/chat/completions', [
            'timeout' => 60,
            'headers' => ['Authorization' => $scheme . ' ' . $key, 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'model'      => $s['byok_model'],
                'max_tokens' => 60,
                'messages'   => [['role' => 'user', 'content' => 'سلام؛ فقط در یک جمله بگو اتصال برقرار است.']],
            ]),
        ]);
        if (is_wp_error($response)) {
            return new WP_Error('botpress_ai_byok_http', 'اتصال برقرار نشد: ' . $response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code !== 200 || !is_array($data)) {
            $message = is_array($data) ? ($data['error']['message'] ?? $data['message'] ?? '') : '';
            return new WP_Error('botpress_ai_byok_error', trim("خطای {$code} از سرویس مدل زبانی. {$message}"));
        }
        return [
            'reply'       => (string) ($data['choices'][0]['message']['content'] ?? ''),
            'model'       => $s['byok_model'],
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }
}
