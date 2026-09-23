<?php

defined('ABSPATH') || exit;

class BotPress_Queue_Manager {
    public function add(int $post_id, string $scheduled_at, string $target = 'both', ?int $channel_id = null) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}botpress_publish_queue
             WHERE post_id = %d AND status = 'pending'
             LIMIT 1",
            $post_id
        ));

        if ($existing) {
            return (int) $existing;
        }

        $result = $wpdb->insert($wpdb->prefix . 'botpress_publish_queue', [
            'post_id'        => $post_id,
            'channel_id'     => $channel_id,
            'publish_target' => $target,
            'status'         => 'pending',
            'scheduled_at'   => $scheduled_at,
            'attempts'       => 0,
            'max_attempts'   => 3,
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    public function cancel(int $queue_id): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'     => 'failed',
                'last_error' => 'لغو شده توسط کاربر',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $queue_id, 'status' => 'pending']
        );
    }

    public function cancel_by_post(int $post_id): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'     => 'failed',
                'last_error' => 'لغو شده توسط کاربر',
                'updated_at' => current_time('mysql'),
            ],
            ['post_id' => $post_id, 'status' => 'pending']
        );
    }

    public function get_pending(int $limit = 20): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, p.post_title
             FROM {$wpdb->prefix}botpress_publish_queue q
             LEFT JOIN {$wpdb->prefix}posts p ON p.ID = q.post_id
             WHERE q.status = 'pending'
             ORDER BY q.scheduled_at ASC
             LIMIT %d",
            $limit
        )) ?: [];
    }

    public function get_all(int $limit = 100): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, p.post_title
             FROM {$wpdb->prefix}botpress_publish_queue q
             LEFT JOIN {$wpdb->prefix}posts p ON p.ID = q.post_id
             ORDER BY q.scheduled_at DESC
             LIMIT %d",
            $limit
        )) ?: [];
    }

    public function get_due(): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}botpress_publish_queue
             WHERE status = 'pending'
             AND scheduled_at <= %s
             ORDER BY scheduled_at ASC
             LIMIT 10",
            current_time('mysql')
        )) ?: [];
    }

    public function mark_processing(int $id): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            ['status' => 'processing', 'updated_at' => current_time('mysql')],
            ['id' => $id]
        );
    }

    public function mark_published(int $id): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'       => 'published',
                'published_at' => current_time('mysql'),
                'updated_at'   => current_time('mysql'),
            ],
            ['id' => $id]
        );
    }

    public function mark_failed(int $id, string $error, int $attempts): void {
        global $wpdb;

        $max = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT max_attempts FROM {$wpdb->prefix}botpress_publish_queue WHERE id = %d",
            $id
        ));

        $new_status = $attempts >= $max ? 'failed' : 'pending';

        $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'     => $new_status,
                'attempts'   => $attempts,
                'last_error' => $error,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        );
    }
}
