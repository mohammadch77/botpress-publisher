<?php

defined('ABSPATH') || exit;

class BotPress_Start_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $name = $context['from']['first_name'] ?? '';

        $text = "سلام {$name}! 👋\n\n"
            . "به ربات BotPress Publisher خوش آمدید.\n\n"
            . "از طریق این ربات می‌توانید:\n"
            . "📝 مقالات وردپرس را مدیریت کنید\n"
            . "📅 انتشار را زمان‌بندی کنید\n"
            . "📢 محتوا را در کانال‌ها منتشر کنید\n\n"
            . "دستورات موجود:\n"
            . "/posts — لیست مقالات\n"
            . "/status — وضعیت سیستم\n"
            . "/help — راهنما";

        return $this->reply($context, $text);
    }
}
