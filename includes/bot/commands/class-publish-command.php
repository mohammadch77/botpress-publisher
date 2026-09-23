<?php

defined('ABSPATH') || exit;

class BotPress_Publish_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        return $this->reply($context, '⚡ انتشار فوری به‌زودی اضافه می‌شود.');
    }

    public function handle_callback(array $context): array {
        return $context['driver']->send_message($context['chat_id'], '⚡ انتشار فوری به‌زودی اضافه می‌شود.');
    }
}
