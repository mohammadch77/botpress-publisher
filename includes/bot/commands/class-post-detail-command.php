<?php

defined('ABSPATH') || exit;

class BotPress_Post_Detail_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);
        if (!$post_id) {
            return $this->reply($context, '❓ لطفاً شناسه مقاله را وارد کنید. مثال: /post 12');
        }
        return $this->send_detail($context['driver'], $context['chat_id'], $post_id);
    }

    public function handle_callback(array $context): array {
        $post_id = (int) $context['value'];
        return $this->send_detail($context['driver'], $context['chat_id'], $post_id, (int) $context['message_id']);
    }

    private function send_detail(BotPress_Bot_Driver_Interface $driver, string $chat_id, int $post_id, ?int $message_id = null): array {
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            $text = '⚠️ مقاله‌ای با این شناسه یافت نشد.';
            return $message_id
                ? $driver->edit_message($chat_id, $message_id, $text)
                : $driver->send_message($chat_id, $text);
        }

        $status_labels = [
            'draft'   => 'Draft',
            'publish' => 'Published',
            'future'  => 'Scheduled',
        ];
        $status = $status_labels[$post->post_status] ?? $post->post_status;
        $author = get_the_author_meta('display_name', $post->post_author);
        $category = wp_get_post_categories($post->ID, ['fields' => 'names']);
        $category_text = !empty($category) ? implode(', ', $category) : '—';

        $text = "📄 " . esc_html($post->post_title) . "\n\n"
            . "📊 وضعیت: {$status}\n"
            . "📅 تاریخ: " . $this->format_date($post->post_date) . "\n"
            . "✍️ نویسنده: " . esc_html($author) . "\n"
            . "🏷 دسته: " . esc_html($category_text) . "\n\n"
            . "📝 خلاصه:\n" . esc_html($this->get_post_excerpt($post)) . "\n\n"
            . "━━━━━━━━━━━━━━";

        $buttons = [
            [
                ['text' => 'انتشار همین الان ⚡', 'callback_data' => 'publish_now:' . $post->ID],
                ['text' => 'زمان‌بندی 📅', 'callback_data' => 'schedule_post:' . $post->ID],
            ],
            [
                ['text' => '🔙 برگشت', 'callback_data' => 'posts_list:0'],
            ],
        ];

        $options = $this->inline_keyboard($buttons);

        return $message_id
            ? $driver->edit_message($chat_id, $message_id, $text, $options)
            : $driver->send_message($chat_id, $text, $options);
    }
}
