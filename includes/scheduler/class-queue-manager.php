<?php

defined('ABSPATH') || exit;

class BotPress_Queue_Manager {
    /** Quick-schedule options shared by the bot keyboard and the admin panel. */
    public static function presets(): array {
        return [
            '+1hour'      => '⚡ ۱ ساعت دیگر',
            '+3hours'     => '🕒 ۳ ساعت دیگر',
            'tomorrow_8'  => '🌅 فردا ۸ صبح',
            'tomorrow_12' => '☀️ فردا ۱۲ ظهر',
            'day_after_8' => '📅 پس‌فردا ۸ صبح',
        ];
    }

    /** Resolves a preset key to a site-local 'Y-m-d H:i:s' string (the format stored in scheduled_at). */
    public static function preset_time(string $key): ?string {
        $now = current_time('timestamp');

        return match ($key) {
            '+1hour'      => date('Y-m-d H:i:s', $now + HOUR_IN_SECONDS),
            '+3hours'     => date('Y-m-d H:i:s', $now + 3 * HOUR_IN_SECONDS),
            'tomorrow_8'  => date('Y-m-d 08:00:00', $now + DAY_IN_SECONDS),
            'tomorrow_12' => date('Y-m-d 12:00:00', $now + DAY_IN_SECONDS),
            'day_after_8' => date('Y-m-d 08:00:00', $now + 2 * DAY_IN_SECONDS),
            default       => null,
        };
    }

    /** scheduled_at is site-local time, so compare against site-local "now", not time() (UTC). */
    public static function is_future(string $scheduled_at): bool {
        $timestamp = strtotime($scheduled_at);
        return $timestamp !== false && $timestamp > current_time('timestamp');
    }

    public function find_pending_for_post(int $post_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}botpress_publish_queue
             WHERE post_id = %d AND status = 'pending'
             ORDER BY scheduled_at ASC LIMIT 1",
            $post_id
        )) ?: null;
    }

    public function add(int $post_id, string $scheduled_at, string $target = 'both', ?int $channel_id = null) {
        global $wpdb;

        if (!get_post($post_id)) {
            return false;
        }

        if (!self::is_future($scheduled_at)) {
            return false;
        }
        $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));

        if ($channel_id !== null) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}botpress_publish_queue
                 WHERE post_id = %d AND channel_id = %d AND status = 'pending'
                 LIMIT 1",
                $post_id,
                $channel_id
            ));
        } else {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}botpress_publish_queue
                 WHERE post_id = %d AND channel_id IS NULL AND status = 'pending'
                 LIMIT 1",
                $post_id
            ));
        }

        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'botpress_publish_queue',
                [
                    'scheduled_at'   => $scheduled_at,
                    'publish_target' => $target,
                    'attempts'       => 0,
                    'last_error'     => null,
                    'updated_at'     => current_time('mysql'),
                ],
                ['id' => (int) $existing]
            );
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
                'status'     => 'cancelled',
                'last_error' => 'لغو شده توسط کاربر',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $queue_id, 'status' => 'pending']
        );
    }

    public function retry(int $queue_id): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'     => 'pending',
                'last_error' => null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $queue_id, 'status' => 'failed']
        );
    }

    public function cancel_by_post(int $post_id, string $reason = 'لغو شده توسط کاربر'): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'     => 'cancelled',
                'last_error' => $reason,
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

    public function reschedule(int $id, int $delay_seconds): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'botpress_publish_queue',
            [
                'status'       => 'pending',
                'scheduled_at' => date('Y-m-d H:i:s', current_time('timestamp') + max(1, $delay_seconds)),
                'last_error'   => 'محدودیت نرخ ارسال؛ تلاش مجدد زمان‌بندی شد',
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
