<?php

defined('ABSPATH') || exit;

class BotPress_Pending_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        return $this->reply($context, '📋 صف انتشار به‌زودی اضافه می‌شود.');
    }
}
