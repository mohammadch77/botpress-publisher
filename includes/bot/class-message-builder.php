<?php

defined('ABSPATH') || exit;

class BotPress_Message_Builder {
    public function build(int $post_id, string $template): string {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }

        $excerpt = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');

        $variables = [
            'title'   => $post->post_title,
            'excerpt' => $excerpt,
            'url'     => get_permalink($post),
            'date'    => wp_date('Y/m/d H:i', strtotime($post->post_date)),
            'author'  => get_the_author_meta('display_name', $post->post_author),
        ];

        return BotPress_Template_Engine::render($template, $variables);
    }
}
