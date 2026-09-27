<?php

defined('ABSPATH') || exit;

class BotPress_Post_Detail_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);
        if (!$post_id) {
            return $this->reply($context, '❓ لطفاً شناسه مقاله را وارد کنید. مثال: `/post 12`');
        }
        return $this->send_detail($context, $post_id);
    }

    public function handle_callback(array $context): array {
        return $this->send_detail($context, (int) $context['value']);
    }

    private function send_detail(array $context, int $post_id): array {
        $post = $post_id ? get_post($post_id) : null;

        if (!$post || $post->post_type !== 'post') {
            return $this->respond($context, '⚠️ مقاله‌ای با این شناسه یافت نشد.');
        }

        $author = get_the_author_meta('display_name', $post->post_author);
        $categories = wp_get_post_categories($post->ID, ['fields' => 'names']);
        $category_text = !empty($categories) ? implode('، ', $categories) : '—';

        $text = '📄 *' . $this->md($post->post_title) . "*\n\n"
            . '📊 وضعیت: ' . $this->status_label($post->post_status) . "\n"
            . '📅 تاریخ: ' . $this->format_date($post->post_date) . "\n"
            . '✍️ نویسنده: ' . $this->md($author) . "\n"
            . '🏷 دسته: ' . $this->md($category_text) . "\n"
            . '🆔 شناسه: `' . $post->ID . "`\n\n"
            . "📝 خلاصه:\n" . $this->md($this->get_post_excerpt($post));

        if ($post->post_status === 'publish') {
            $text .= "\n\n" . BotPress_Markdown::link('🔗 مشاهده مقاله', (string) get_permalink($post));
        }

        $buttons = [
            [
                ['text' => '⚡ انتشار همین الان', 'callback_data' => 'publish_now:' . $post->ID],
                ['text' => '📅 زمان‌بندی', 'callback_data' => 'schedule_post:' . $post->ID],
            ],
            [
                ['text' => '🔙 برگشت به لیست', 'callback_data' => 'posts_list:|0'],
            ],
        ];

        return $this->respond($context, $text, $this->inline_keyboard($buttons));
    }
}
