<?php

defined('ABSPATH') || exit;

/**
 * Telegram bot driver. Implementation lands in Phase 3.
 */
class BotPress_Telegram_Driver implements BotPress_Bot_Driver_Interface {
    private string $token;

    public function __construct(string $token) {
        $this->token = $token;
    }

    public function send_message(string $chat_id, string $text, array $options = []): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }

    public function set_webhook(string $url): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }

    public function get_me(): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }
}
