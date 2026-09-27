<?php

defined('ABSPATH') || exit;

class BotPress_Telegram_Driver implements BotPress_Bot_Driver_Interface {
    private string $base_url = 'https://api.telegram.org/bot';
    private string $token;

    public function __construct(string $token) {
        $this->token = trim($token);
    }

    private function call(string $method, array $params = []): array {
        $result = $this->request($method, $params);
        if (!($result['ok'] ?? false) && class_exists('BotPress_Debug_Log')) {
            BotPress_Debug_Log::add('telegram', 'error', "خطای API در {$method}", (string) ($result['description'] ?? 'unknown'));
        }
        return $result;
    }

    private function request(string $method, array $params = []): array {
        $url = $this->base_url . $this->token . '/' . $method;

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $params ? wp_json_encode($params) : '{}',
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'description' => 'اتصال به سرور تلگرام ناموفق بود (ممکن است روی هاست ایران فیلتر باشد): ' . $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            $code = wp_remote_retrieve_response_code($response);
            return ['ok' => false, 'description' => "پاسخ نامعتبر از سرور تلگرام (HTTP {$code})"];
        }
        return $body;
    }

    // Retries as plain text if Telegram rejects the Markdown ("can't parse entities").
    private function send_formatted(string $method, array $params): array {
        $params['parse_mode'] = 'Markdown';
        $result = $this->call($method, $params);
        if (($result['ok'] ?? false) || (int) ($result['error_code'] ?? 0) !== 400) {
            return $result;
        }
        if (stripos((string) ($result['description'] ?? ''), 'not modified') !== false) {
            return $result;
        }
        unset($params['parse_mode']);
        foreach (['text', 'caption'] as $key) {
            if (isset($params[$key])) {
                $params[$key] = BotPress_Markdown::to_plain($params[$key]);
            }
        }
        return $this->call($method, $params);
    }

    public function send_message(string $chat_id, string $text, array $options = []): array {
        return $this->send_formatted('sendMessage', array_merge([
            'chat_id' => $chat_id,
            'text'    => $text,
        ], $options));
    }

    public function send_photo(string $chat_id, string $photo_url, string $caption = '', array $options = []): array {
        return $this->send_formatted('sendPhoto', array_merge([
            'chat_id' => $chat_id,
            'photo'   => $photo_url,
            'caption' => $caption,
        ], $options));
    }

    public function edit_message(string $chat_id, int $message_id, string $text, array $options = []): array {
        return $this->send_formatted('editMessageText', array_merge([
            'chat_id'    => $chat_id,
            'message_id' => $message_id,
            'text'       => $text,
        ], $options));
    }

    public function delete_message(string $chat_id, int $message_id): bool {
        $result = $this->call('deleteMessage', [
            'chat_id'    => $chat_id,
            'message_id' => $message_id,
        ]);
        return $result['ok'] ?? false;
    }

    public function answer_callback(string $callback_id, string $text = '', bool $show_alert = false): bool {
        $result = $this->call('answerCallbackQuery', [
            'callback_query_id' => $callback_id,
            'text'              => $text,
            'show_alert'        => $show_alert,
        ]);
        return $result['ok'] ?? false;
    }

    public function get_chat(string $chat_id): array {
        return $this->call('getChat', ['chat_id' => $chat_id]);
    }

    public function get_me(): array {
        return $this->call('getMe');
    }

    public function set_webhook(string $url, string $secret = ''): array {
        $params = ['url' => $url];
        if ($secret) {
            $params['secret_token'] = $secret;
        }
        return $this->call('setWebhook', $params);
    }

    public function delete_webhook(): array {
        return $this->call('deleteWebhook');
    }

    public function get_webhook_info(): array {
        return $this->call('getWebhookInfo');
    }

    public function get_platform(): string {
        return 'telegram';
    }
}
