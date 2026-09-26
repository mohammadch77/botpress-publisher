<?php

defined('ABSPATH') || exit;

class BotPress_WordPress_Publisher {
    public function publish(int $post_id): array {
        $post = get_post($post_id);

        if (!$post) {
            return ['success' => false, 'error' => 'مقاله یافت نشد'];
        }

        if ($post->post_status === 'publish') {
            return [
                'success'            => true,
                'url'                => get_permalink($post_id),
                'already_published'  => true,
            ];
        }

        $result = wp_update_post([
            'ID'            => $post_id,
            'post_status'   => 'publish',
            'post_date'     => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
        ], true);

        if (is_wp_error($result)) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }

        if (empty($result)) {
            return ['success' => false, 'error' => 'بروزرسانی مقاله ناموفق بود'];
        }

        return [
            'success'           => true,
            'url'               => get_permalink($post_id),
            'already_published' => false,
        ];
    }

    public function get_featured_image_url(int $post_id): ?string {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            return null;
        }

        $image = wp_get_attachment_image_src($thumbnail_id, 'large');
        return $image ? $image[0] : null;
    }
}
