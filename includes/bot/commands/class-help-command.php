<?php

defined('ABSPATH') || exit;

class BotPress_Help_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $text = "📖 <b>راهنمای دستورات</b>\n\n"
            . "/start — شروع و خوش‌آمدگویی\n"
            . "/posts — لیست ۱۰ مقاله اخیر\n"
            . "/post [id] — جزئیات یک مقاله\n"
            . "/status — وضعیت سیستم و کانال‌ها\n"
            . "/channels — لیست کانال‌های متصل\n"
            . "/schedule — زمان‌بندی انتشار (به‌زودی)\n"
            . "/publish — انتشار فوری (به‌زودی)\n"
            . "/pending — صف انتشار (به‌زودی)\n"
            . "/cancel — لغو یک آیتم صف (به‌زودی)\n"
            . "/help — همین راهنما";

        return $this->reply($context, $text);
    }
}
