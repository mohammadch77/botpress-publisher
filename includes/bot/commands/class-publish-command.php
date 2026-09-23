<?php

defined('ABSPATH') || exit;

class BotPress_Publish_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);

        if (!$post_id) {
            return $this->reply(
                $context,
                "⚡ <b>انتشار فوری</b>\n\n" .
                "استفاده: <code>/publish [شناسه مقاله]</code>\n\n" .
                "مثال: <code>/publish 42</code>\n\n" .
                "یا از /posts لیست مقالات را ببینید و دکمه انتشار را بزنید."
            );
        }

        return $this->do_publish($context, $post_id);
    }

    public function handle_callback(array $context): array {
        $post_id = (int) $context['value'];
        return $this->do_publish($context, $post_id);
    }

    private function do_publish(array $context, int $post_id): array {
        $driver = $context['driver'];
        $chat_id = $context['chat_id'];
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return $this->reply($context, "❌ مقاله‌ای با شناسه <code>{$post_id}</code> یافت نشد.");
        }

        $driver->send_message($chat_id, "⏳ در حال انتشار <b>" . esc_html($post->post_title) . "</b>...");

        $engine = new BotPress_Publisher_Engine();
        $result = $engine->publish_now($post_id, 'both');

        if ($result['success']) {
            $url = $result['wordpress']['url'] ?? get_permalink($post_id);
            $channel_count = count(array_filter($result['channels'], static fn($c) => $c['success']));

            $message = "✅ <b>انتشار موفق!</b>\n\n";
            $message .= "📄 <b>" . esc_html($post->post_title) . "</b>\n";
            $message .= "🔗 <a href=\"{$url}\">مشاهده مقاله</a>\n\n";

            if ($channel_count > 0) {
                $message .= "📢 ارسال شد به <b>{$channel_count}</b> کانال";
            }

            return $this->reply($context, $message);
        }

        $message = "❌ <b>خطا در انتشار</b>\n\n";

        if ($result['wordpress'] && !$result['wordpress']['success']) {
            $message .= "وردپرس: " . ($result['wordpress']['error'] ?? 'خطای ناشناخته') . "\n";
        }

        foreach ($result['channels'] as $channel_result) {
            if (!$channel_result['success']) {
                $message .= "کانال {$channel_result['channel_name']}: " . ($channel_result['error'] ?? 'خطا') . "\n";
            }
        }

        return $this->reply($context, $message);
    }
}
