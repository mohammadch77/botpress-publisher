<?php

defined('ABSPATH') || exit;

class BotPress_Cron_Scheduler {
    public static function register(): void {
        add_action('botpress_process_queue', [__CLASS__, 'process_queue']);
    }

    public static function first_error(array $result): string {
        if (!empty($result['error'])) {
            return (string) $result['error'];
        }
        if (isset($result['wordpress']['success']) && !$result['wordpress']['success']) {
            return 'وردپرس: ' . ($result['wordpress']['error'] ?? 'خطای ناشناخته');
        }
        foreach ($result['channels'] ?? [] as $channel) {
            if (empty($channel['success'])) {
                return 'کانال ' . ($channel['channel_name'] ?? '') . ': ' . ($channel['error'] ?? 'خطا');
            }
        }
        return 'خطا در انتشار';
    }

    private const LOCK = 'botpress_queue_lock';
    private const THROTTLE = 'botpress_queue_last_check';

    /** Fallback trigger for sites where page caching keeps WP-Cron from firing on time. */
    public static function maybe_process(): void {
        if (get_transient(self::THROTTLE)) {
            return;
        }
        set_transient(self::THROTTLE, 1, 30);
        if (!(new BotPress_Queue_Manager())->get_due()) {
            return;
        }
        // Send the HTTP response first so the admin/bot request isn't held up by publishing.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        self::process_queue();
    }

    public static function process_queue(): void {
        if (get_transient(self::LOCK)) {
            return;
        }
        set_transient(self::LOCK, 1, 5 * MINUTE_IN_SECONDS);

        try {
            self::process_due_items();
        } finally {
            delete_transient(self::LOCK);
        }
    }

    private static function process_due_items(): void {
        $queue_manager = new BotPress_Queue_Manager();
        $due_items = $queue_manager->get_due();

        if (empty($due_items)) {
            return;
        }

        $engine = new BotPress_Publisher_Engine();

        foreach ($due_items as $item) {
            $queue_manager->mark_processing((int) $item->id);

            try {
                $result = $engine->publish_now(
                    (int) $item->post_id,
                    $item->publish_target,
                    $item->channel_id ? (int) $item->channel_id : null
                );

                $rate_limited_channel = null;
                foreach (($result['channels'] ?? []) as $channel_result) {
                    if (!empty($channel_result['rate_limited'])) {
                        $rate_limited_channel = $channel_result;
                        break;
                    }
                }

                $post = get_post((int) $item->post_id);

                if ($result['success']) {
                    $queue_manager->mark_published((int) $item->id);
                    if ($post) {
                        BotPress_Notifier::published($post, $result, 'queue');
                    }
                } elseif ($rate_limited_channel) {
                    $queue_manager->reschedule((int) $item->id, (int) ($rate_limited_channel['retry_after'] ?? 30));
                } else {
                    $attempts = (int) $item->attempts + 1;
                    $error = self::first_error($result);
                    $queue_manager->mark_failed((int) $item->id, $error, $attempts);
                    if ($post && $attempts >= (int) $item->max_attempts) {
                        BotPress_Notifier::failed($post, $error, 'queue');
                    }
                }
            } catch (Throwable $e) {
                $attempts = (int) $item->attempts + 1;
                $queue_manager->mark_failed((int) $item->id, $e->getMessage(), $attempts);
                error_log('BotPress cron error: ' . $e->getMessage());
            }
        }
    }
}
