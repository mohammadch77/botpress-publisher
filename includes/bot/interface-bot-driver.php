<?php

defined('ABSPATH') || exit;

interface BotPress_Bot_Driver_Interface {
    public function send_message(string $chat_id, string $text, array $options = []): array;
    public function send_photo(string $chat_id, string $photo_url, string $caption = '', array $options = []): array;
    public function edit_message(string $chat_id, int $message_id, string $text, array $options = []): array;
    public function delete_message(string $chat_id, int $message_id): bool;
    public function answer_callback(string $callback_id, string $text = '', bool $show_alert = false): bool;
    public function get_chat(string $chat_id): array;
    public function get_me(): array;
    public function set_webhook(string $url, string $secret = ''): array;
    public function delete_webhook(): array;
    public function get_platform(): string;
}
