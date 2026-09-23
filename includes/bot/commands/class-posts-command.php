<?php

defined('ABSPATH') || exit;

class BotPress_Posts_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        return $this->send_list($context['driver'], $context['chat_id']);
    }

    public function handle_callback(array $context): array {
        return $this->send_list($context['driver'], $context['chat_id'], (int) $context['message_id']);
    }

    private function send_list(BotPress_Bot_Driver_Interface $driver, string $chat_id, ?int $message_id = null): array {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => ['draft', 'publish', 'future'],
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (empty($posts)) {
            $text = '📋 مقاله‌ای یافت نشد.';
            return $message_id
                ? $driver->edit_message($chat_id, $message_id, $text)
                : $driver->send_message($chat_id, $text);
        }

        $text = "📋 <b>مقالات اخیر</b>\n\n";
        $buttons = [];
        $i = 1;
        foreach ($posts as $post) {
            $status = $post->post_status === 'publish' ? 'published' : $post->post_status;
            $text .= "{$i}. " . esc_html($post->post_title) . " — <i>{$status}</i>\n";
            $buttons[] = [[
                'text'          => "{$i}. جزئیات 🔍",
                'callback_data' => 'post_detail:' . $post->ID,
            ]];
            $i++;
        }
        $text .= "\nبرای جزئیات روی دکمه‌ها کلیک کنید:";

        $options = $this->inline_keyboard($buttons);

        return $message_id
            ? $driver->edit_message($chat_id, $message_id, $text, $options)
            : $driver->send_message($chat_id, $text, $options);
    }
}
