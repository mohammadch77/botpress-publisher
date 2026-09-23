<?php

defined('ABSPATH') || exit;

class BotPress_Cancel_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $queue_id = (int) ($context['args'][0] ?? 0);

        if (!$queue_id) {
            return $this->reply(
                $context,
                "❌ <b>لغو زمان‌بندی</b>\n\n" .
                "استفاده: <code>/cancel [شناسه صف]</code>\n\n" .
                "شناسه صف را از /pending ببینید."
            );
        }

        return $this->do_cancel($context, $queue_id);
    }

    public function handle_callback(array $context): array {
        $queue_id = (int) $context['value'];
        return $this->do_cancel($context, $queue_id);
    }

    private function do_cancel(array $context, int $queue_id): array {
        $queue_manager = new BotPress_Queue_Manager();
        $success = $queue_manager->cancel($queue_id);

        if ($success) {
            return $this->reply(
                $context,
                "✅ زمان‌بندی با شناسه <code>{$queue_id}</code> لغو شد.\n\n" .
                "برای مشاهده صف: /pending"
            );
        }

        return $this->reply(
            $context,
            "❌ لغو ناموفق بود.\n" .
            "ممکن است این مورد قبلاً منتشر یا لغو شده باشد."
        );
    }
}
