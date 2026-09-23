<?php

defined('ABSPATH') || exit;

class BotPress_Schedule_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        return $this->reply($context, '📅 زمان‌بندی انتشار به‌زودی اضافه می‌شود.');
    }

    public function handle_callback(array $context): array {
        return $context['driver']->send_message($context['chat_id'], '📅 زمان‌بندی انتشار به‌زودی اضافه می‌شود.');
    }
}
