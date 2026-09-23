<?php

defined('ABSPATH') || exit;

class BotPress_Publisher_Engine {
    private BotPress_WordPress_Publisher $wp_publisher;
    private BotPress_Channel_Publisher $channel_publisher;

    public function __construct() {
        $this->wp_publisher = new BotPress_WordPress_Publisher();
        $this->channel_publisher = new BotPress_Channel_Publisher();
    }

    public function publish_now(int $post_id, string $target = 'both', ?int $channel_id = null): array {
        $post = get_post($post_id);
        if (!$post) {
            return ['success' => false, 'error' => 'مقاله یافت نشد'];
        }

        $wp_result = null;
        $channel_results = [];
        $overall_success = true;

        if (in_array($target, ['wordpress', 'both'], true)) {
            $wp_result = $this->wp_publisher->publish($post_id);
            if (!$wp_result['success']) {
                $overall_success = false;
            }
            $this->log(
                $post_id,
                null,
                'publish_wordpress',
                $wp_result['success'] ? 'success' : 'failed',
                $wp_result['error'] ?? 'انتشار در وردپرس',
                'wordpress'
            );
        }

        if (in_array($target, ['channel', 'both'], true)) {
            $post = get_post($post_id);

            if ($channel_id) {
                global $wpdb;
                $channel = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}botpress_channels WHERE id = %d AND is_active = 1",
                    $channel_id
                ));
                if ($channel) {
                    $channel_results[] = $this->channel_publisher->publish_to_channel($post, $channel);
                }
            } else {
                $channel_results = $this->channel_publisher->publish_to_all($post);
            }

            foreach ($channel_results as $result) {
                if (!$result['success']) {
                    $overall_success = false;
                }
                $this->log(
                    $post_id,
                    $result['channel_id'],
                    'publish_channel',
                    $result['success'] ? 'success' : 'failed',
                    $result['error'] ?? ('ارسال به کانال ' . ($result['channel_name'] ?? '')),
                    $result['platform'] ?? null
                );
            }
        }

        return [
            'success'   => $overall_success,
            'post_id'   => $post_id,
            'wordpress' => $wp_result,
            'channels'  => $channel_results,
        ];
    }

    private function log(
        int $post_id,
        ?int $channel_id,
        string $action,
        string $status,
        string $message,
        ?string $platform
    ): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'botpress_logs', [
            'post_id'    => $post_id,
            'channel_id' => $channel_id,
            'action'     => $action,
            'platform'   => $platform,
            'status'     => $status,
            'message'    => $message,
            'created_at' => current_time('mysql'),
        ]);
    }
}
