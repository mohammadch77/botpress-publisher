<?php

defined('ABSPATH') || exit;

class BotPress_REST_API {
    private string $namespace = 'botpress/v1';

    public function register_routes(): void {
        register_rest_route($this->namespace, '/dashboard/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_dashboard_stats'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/channels', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_channels'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'create_channel'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/channels/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'update_channel'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'delete_channel'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/channels/(?P<id>\d+)/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_channel'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'save_settings'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/logs', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_logs'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'clear_logs'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/queue', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_queue'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/posts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/templates', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_templates'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'save_template'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/webhook/set', [
            'methods'             => 'POST',
            'callback'            => [$this, 'set_webhook'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Webhook endpoints — no auth, signature/secret verified inside the handler.
        register_rest_route($this->namespace, '/webhook/telegram', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_telegram_webhook'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/webhook/bale', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_bale_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function check_permission(): bool {
        return current_user_can('manage_options');
    }

    public function handle_telegram_webhook(WP_REST_Request $request): void {
        (new BotPress_Webhook_Handler())->handle('telegram');
    }

    public function handle_bale_webhook(WP_REST_Request $request): void {
        (new BotPress_Webhook_Handler())->handle('bale');
    }

    public function get_dashboard_stats(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $channels_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_channels");

        $published_today = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'published' AND DATE(published_at) = CURDATE()"
        );

        $pending_queue = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'pending'"
        );

        $failed_today = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'failed' AND DATE(updated_at) = CURDATE()"
        );

        $recent_logs = $wpdb->get_results(
            "SELECT id, post_id, channel_id, action, platform, status, message, created_at
             FROM {$wpdb->prefix}botpress_logs ORDER BY id DESC LIMIT 10"
        );

        return new WP_REST_Response([
            'total_channels'   => $channels_count,
            'published_today'  => $published_today,
            'pending_in_queue' => $pending_queue,
            'failed_today'     => $failed_today,
            'recent_activity'  => $recent_logs,
        ], 200);
    }

    public function get_channels(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $channels = $wpdb->get_results(
            "SELECT id, name, platform, chat_id, bot_username, is_active, last_error, last_used_at
             FROM {$wpdb->prefix}botpress_channels ORDER BY id DESC"
        );

        foreach ($channels as $channel) {
            $channel->is_active = (bool) $channel->is_active;
        }

        return new WP_REST_Response(['channels' => $channels], 200);
    }

    public function create_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $name = sanitize_text_field((string) $request->get_param('name'));
        $platform = sanitize_text_field((string) $request->get_param('platform'));
        $chat_id = sanitize_text_field((string) $request->get_param('chat_id'));
        $bot_token = (string) $request->get_param('bot_token');

        if (!$name || !in_array($platform, ['telegram', 'bale'], true) || !$chat_id || !$bot_token) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_params'], 400);
        }

        $now = current_time('mysql');

        $wpdb->insert($wpdb->prefix . 'botpress_channels', [
            'name'          => $name,
            'platform'      => $platform,
            'chat_id'       => $chat_id,
            'bot_token_enc' => BotPress_Encryption::encrypt($bot_token),
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return new WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id], 200);
    }

    public function update_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');

        $data = ['updated_at' => current_time('mysql')];

        if ($request->get_param('name') !== null) {
            $data['name'] = sanitize_text_field((string) $request->get_param('name'));
        }
        if ($request->get_param('chat_id') !== null) {
            $data['chat_id'] = sanitize_text_field((string) $request->get_param('chat_id'));
        }
        if ($request->get_param('is_active') !== null) {
            $data['is_active'] = $request->get_param('is_active') ? 1 : 0;
        }
        if ($request->get_param('bot_token')) {
            $data['bot_token_enc'] = BotPress_Encryption::encrypt((string) $request->get_param('bot_token'));
        }

        $wpdb->update($wpdb->prefix . 'botpress_channels', $data, ['id' => $id]);

        return new WP_REST_Response(['success' => true, 'id' => $id], 200);
    }

    public function delete_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');
        $wpdb->delete($wpdb->prefix . 'botpress_channels', ['id' => $id]);
        return new WP_REST_Response(['success' => true], 200);
    }

    public function test_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');

        $channel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}botpress_channels WHERE id = %d", $id
        ));

        if (!$channel) {
            return new WP_REST_Response(['success' => false, 'message' => 'channel_not_found'], 404);
        }

        $driver = BotPress_Driver_Factory::make_from_channel($channel);
        if (!$driver) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_token'], 200);
        }

        $result = $driver->get_chat($channel->chat_id);
        $success = $result['ok'] ?? false;

        $wpdb->update($wpdb->prefix . 'botpress_channels', [
            'last_error'   => $success ? null : ($result['description'] ?? 'unknown_error'),
            'last_used_at' => current_time('mysql'),
            'updated_at'   => current_time('mysql'),
        ], ['id' => $id]);

        return new WP_REST_Response(['success' => $success, 'result' => $result], 200);
    }

    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'telegram' => $this->platform_settings('telegram'),
            'bale'     => $this->platform_settings('bale'),
            'authorized_users'  => get_option('botpress_authorized_users', []),
            'notify_on_publish' => (bool) get_option('botpress_notify_on_publish', true),
            'notify_on_fail'    => (bool) get_option('botpress_notify_on_fail', true),
        ], 200);
    }

    private function platform_settings(string $platform): array {
        $encrypted = get_option("botpress_bot_token_{$platform}_enc", '');
        $has_token = !empty($encrypted);
        $token_masked = $has_token ? BotPress_Encryption::mask(BotPress_Encryption::decrypt($encrypted)) : '';

        return [
            'token_masked' => $token_masked,
            'has_token'    => $has_token,
            'webhook_url'  => rest_url("botpress/v1/webhook/{$platform}"),
            'webhook_set'  => (bool) get_option("botpress_webhook_set_{$platform}", false),
            'connected'    => $has_token,
        ];
    }

    public function save_settings(WP_REST_Request $request): WP_REST_Response {
        $telegram_token = $request->get_param('telegram_token');
        $bale_token = $request->get_param('bale_token');

        if (!empty($telegram_token)) {
            update_option('botpress_bot_token_telegram_enc', BotPress_Encryption::encrypt((string) $telegram_token));
        }
        if (!empty($bale_token)) {
            update_option('botpress_bot_token_bale_enc', BotPress_Encryption::encrypt((string) $bale_token));
        }

        if ($request->get_param('authorized_users') !== null) {
            $users = array_map('sanitize_text_field', (array) $request->get_param('authorized_users'));
            update_option('botpress_authorized_users', array_values(array_filter($users)));
        }

        if ($request->get_param('notify_on_publish') !== null) {
            update_option('botpress_notify_on_publish', (bool) $request->get_param('notify_on_publish'));
        }
        if ($request->get_param('notify_on_fail') !== null) {
            update_option('botpress_notify_on_fail', (bool) $request->get_param('notify_on_fail'));
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_logs(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}botpress_logs ORDER BY id DESC LIMIT 100");
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_logs");
        return new WP_REST_Response(['logs' => $logs, 'total' => $total], 200);
    }

    public function clear_logs(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}botpress_logs");
        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_queue(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $queue = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}botpress_publish_queue ORDER BY id DESC LIMIT 100");
        return new WP_REST_Response(['queue' => $queue], 200);
    }

    public function get_posts(WP_REST_Request $request): WP_REST_Response {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => ['draft', 'publish', 'future'],
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $data = array_map(static fn($post) => [
            'id'     => $post->ID,
            'title'  => $post->post_title,
            'status' => $post->post_status,
            'date'   => $post->post_date,
        ], $posts);

        return new WP_REST_Response(['posts' => $data], 200);
    }

    public function get_templates(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'template'  => get_option('botpress_default_template', ''),
            'variables' => BotPress_Template_Engine::available_variables(),
        ], 200);
    }

    public function save_template(WP_REST_Request $request): WP_REST_Response {
        $template = (string) $request->get_param('template');
        update_option('botpress_default_template', wp_kses_post($template));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function set_webhook(WP_REST_Request $request): WP_REST_Response {
        $platform = sanitize_text_field((string) $request->get_param('platform'));

        if (!in_array($platform, ['telegram', 'bale'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_platform'], 400);
        }

        $driver = BotPress_Driver_Factory::make($platform);
        if (!$driver) {
            return new WP_REST_Response(['success' => false, 'message' => 'no_token_configured'], 200);
        }

        $url = rest_url("botpress/v1/webhook/{$platform}");
        $secret = $platform === 'telegram' ? get_option('botpress_webhook_secret', '') : '';

        $result = $driver->set_webhook($url, $secret);
        $success = $result['ok'] ?? false;

        update_option("botpress_webhook_set_{$platform}", $success);

        return new WP_REST_Response(['success' => $success, 'result' => $result], 200);
    }
}
