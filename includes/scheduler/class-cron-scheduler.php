<?php

defined('ABSPATH') || exit;

class BotPress_Cron_Scheduler {
    public static function register(): void {
        add_action('botpress_process_queue', [__CLASS__, 'process_queue']);
    }

    public static function process_queue(): void {
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

                if ($result['success']) {
                    $queue_manager->mark_published((int) $item->id);
                } else {
                    $attempts = (int) $item->attempts + 1;
                    $error = $result['wordpress']['error'] ?? 'خطا در انتشار';
                    $queue_manager->mark_failed((int) $item->id, $error, $attempts);
                }
            } catch (Throwable $e) {
                $attempts = (int) $item->attempts + 1;
                $queue_manager->mark_failed((int) $item->id, $e->getMessage(), $attempts);
                error_log('BotPress cron error: ' . $e->getMessage());
            }
        }
    }
}
