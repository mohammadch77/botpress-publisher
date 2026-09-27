<?php

defined('ABSPATH') || exit;

class BotPress_Help_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $text = "📖 *راهنمای دستورات*\n\n"
            . "/start — شروع و خوش‌آمدگویی\n"
            . "/posts — لیست مقالات (`/posts draft|published|scheduled`)\n"
            . "/post — جزئیات یک مقاله (`/post 12`)\n"
            . "/search — جستجوی مقاله (`/search عبارت`)\n"
            . "/status — وضعیت سیستم و کانال‌ها\n"
            . "/channels — لیست کانال‌های متصل\n"
            . "/schedule — زمان‌بندی انتشار (`/schedule 12`)\n"
            . "/publish — انتشار فوری (`/publish 12`)\n"
            . "/pending — صف انتشار\n"
            . "/cancel — لغو یک آیتم صف (`/cancel 5`)\n"
            . "/help — همین راهنما";

        return $this->reply($context, $text);
    }
}
