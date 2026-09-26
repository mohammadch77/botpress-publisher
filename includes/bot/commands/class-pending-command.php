<?php

defined('ABSPATH') || exit;

class BotPress_Pending_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $queue_manager = new BotPress_Queue_Manager();
        $items = $queue_manager->get_pending(10);

        if (empty($items)) {
            return $this->reply(
                $context,
                "📭 صف انتشار خالی است\n\n" .
                "هیچ مقاله‌ای در انتظار انتشار نیست.\n\n" .
                "برای زمان‌بندی: /posts"
            );
        }

        $message = '📋 صف انتشار (' . count($items) . " مورد)\n\n";
        $keyboard = [];

        foreach ($items as $item) {
            $time = wp_date('Y/m/d H:i', strtotime($item->scheduled_at));
            $title = mb_substr($item->post_title ?? 'بدون عنوان', 0, 40);
            $message .= "📄 {$title}\n";
            $message .= "📅 {$time}\n";
            $message .= "🆔 صف: {$item->id}\n\n";

            $keyboard[] = [
                ['text' => "❌ لغو: {$title}", 'callback_data' => "cancel_queue:{$item->id}"],
            ];
        }

        return $this->reply($context, $message, $this->inline_keyboard($keyboard));
    }
}
