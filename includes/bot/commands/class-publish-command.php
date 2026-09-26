<?php

defined('ABSPATH') || exit;

class BotPress_Publish_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);

        if (!$post_id) {
            return $this->reply(
                $context,
                "⚡ انتشار فوری\n\n" .
                "استفاده: /publish [شناسه مقاله]\n\n" .
                "مثال: /publish 42\n\n" .
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
            return $this->reply($context, "❌ مقاله‌ای با شناسه {$post_id} یافت نشد.");
        }

        $driver->send_message($chat_id, "⏳ در حال انتشار " . esc_html($post->post_title) . "...");

        $engine = new BotPress_Publisher_Engine();
        $result = $engine->publish_now($post_id, 'both');

        if ($result['success']) {
            $url = $result['wordpress']['url'] ?? get_permalink($post_id);
            $channel_count = count(array_filter($result['channels'], static fn($c) => $c['success']));

            $message = "✅ انتشار موفق!\n\n";
            $message .= "📄 " . esc_html($post->post_title) . "\n";
            $message .= "🔗 مشاهده مقاله\n\n";

            if ($channel_count > 0) {
                $message .= "📢 ارسال شد به {$channel_count} کانال";
            }

            return $this->reply($context, $message);
        }

        $message = "❌ خطا در انتشار\n\n";

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
