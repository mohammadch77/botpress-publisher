<?php

defined('ABSPATH') || exit;

interface BotPress_Bot_Driver_Interface {
    public function send_message(string $chat_id, string $text, array $options = []): array;
    public function set_webhook(string $url): array;
    public function get_me(): array;
}
