<?php

defined('ABSPATH') || exit;

class BotPress_Channel_Publisher {
    public function publish_to_all(WP_Post $post): array {
        global $wpdb;

        $channels = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}botpress_channels WHERE is_active = 1"
        );

        $results = [];
        foreach ($channels as $channel) {
            $results[] = $this->publish_to_channel($post, $channel);
        }

        return $results;
    }

    public function publish_to_channel(WP_Post $post, object $channel): array {
        global $wpdb;

        $driver = BotPress_Driver_Factory::make_from_channel($channel);

        if (!$driver) {
            return [
                'channel_id'   => (int) $channel->id,
                'channel_name' => $channel->name,
                'platform'     => $channel->platform,
                'success'      => false,
                'error'        => 'درایور کانال در دسترس نیست',
            ];
        }

        $template_engine = new BotPress_Template_Engine('', $channel->platform);
        $text = $template_engine->render($post);
        $image_url = (new BotPress_WordPress_Publisher())->get_featured_image_url($post->ID);

        $keyboard = [
            'inline_keyboard' => [[
                [
                    'text' => '📖 ادامه مطلب',
                    'url'  => get_permalink($post->ID),
                ],
            ]],
        ];

        if ($image_url) {
            $result = $driver->send_photo($channel->chat_id, $image_url, $text, ['reply_markup' => $keyboard]);
        } else {
            $result = $driver->send_message($channel->chat_id, $text, ['reply_markup' => $keyboard]);
        }

        $success = $result['ok'] ?? false;
        $error = $success ? null : ($result['description'] ?? 'خطای ناشناخته');

        if ($success) {
            $wpdb->update(
                $wpdb->prefix . 'botpress_channels',
                ['last_used_at' => current_time('mysql'), 'updated_at' => current_time('mysql')],
                ['id' => $channel->id]
            );
        } else {
            $wpdb->update(
                $wpdb->prefix . 'botpress_channels',
                ['last_error' => $error, 'updated_at' => current_time('mysql')],
                ['id' => $channel->id]
            );
        }

        return [
            'channel_id'   => (int) $channel->id,
            'channel_name' => $channel->name,
            'platform'     => $channel->platform,
            'success'      => $success,
            'message_id'   => $result['result']['message_id'] ?? null,
            'error'        => $error,
        ];
    }
}
