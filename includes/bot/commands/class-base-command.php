<?php

defined('ABSPATH') || exit;

abstract class BotPress_Base_Command {
    protected function reply(array $context, string $text, array $options = []): array {
        return $context['driver']->send_message($context['chat_id'], $text, $options);
    }

    protected function inline_keyboard(array $buttons): array {
        return ['reply_markup' => ['inline_keyboard' => $buttons]];
    }

    protected function get_post_excerpt(WP_Post $post, int $length = 200): string {
        $excerpt = $post->post_excerpt ?: wp_trim_words(
            wp_strip_all_tags($post->post_content), 30, '...'
        );
        return mb_substr($excerpt, 0, $length);
    }

    protected function format_date(string $date): string {
        return wp_date('Y/m/d H:i', strtotime($date));
    }

    protected function safe_html(string $text): string {
        return wp_kses($text, [
            'b'    => [],
            'i'    => [],
            'code' => [],
            'a'    => ['href' => true],
        ]);
    }
}
