<?php

defined('ABSPATH') || exit;

class BotPress_Bale_Driver implements BotPress_Bot_Driver_Interface {
    private string $base_url = 'https://tapi.bale.ai/bot';
    private string $token;

    public function __construct(string $token) {
        $this->token = $token;
    }

    private function call(string $method, array $params = []): array {
        $url = $this->base_url . $this->token . '/' . $method;

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'description' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body ?? ['ok' => false, 'description' => 'Invalid response'];
    }

    public function send_message(string $chat_id, string $text, array $options = []): array {
        return $this->call('sendMessage', array_merge([
            'chat_id' => $chat_id,
            'text'    => $text,
        ], $options));
    }

    public function send_photo(string $chat_id, string $photo_url, string $caption = '', array $options = []): array {
        return $this->call('sendPhoto', array_merge([
            'chat_id' => $chat_id,
            'photo'   => $photo_url,
            'caption' => $caption,
        ], $options));
    }

    public function edit_message(string $chat_id, int $message_id, string $text, array $options = []): array {
        return $this->call('editMessageText', array_merge([
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
        return $this->call('setWebhook', ['url' => $url]);
    }

    public function delete_webhook(): array {
        return $this->call('deleteWebhook');
    }

    public function get_platform(): string {
        return 'bale';
    }
}
