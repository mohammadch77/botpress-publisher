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
                'args'                => $this->channel_args(),
            ],
        ]);

        register_rest_route($this->namespace, '/channels/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'update_channel'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $this->channel_id_arg() + $this->channel_args(false),
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'delete_channel'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $this->channel_id_arg(),
            ],
        ]);

        register_rest_route($this->namespace, '/channels/(?P<id>\d+)/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_channel'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->channel_id_arg(),
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
                'args'                => [
                    'telegram_token' => [
                        'type'              => 'string',
                        'required'          => false,
                        'validate_callback' => [$this, 'validate_bot_token'],
                    ],
                    'bale_token' => [
                        'type'              => 'string',
                        'required'          => false,
                        'validate_callback' => [$this, 'validate_bot_token'],
                    ],
                    'authorized_users' => [
                        'required'          => false,
                        'validate_callback' => static function ($v) {
                            if (!is_array($v)) {
                                return false;
                            }
                            foreach ($v as $id) {
                                if (!preg_match('/^-?\d+$/', (string) $id)) {
                                    return false;
                                }
                            }
                            return true;
                        },
                    ],
                    'notify_on_publish' => ['required' => false],
                    'notify_on_fail'    => ['required' => false],
                ],
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

        register_rest_route($this->namespace, '/logs/export', [
            'methods'             => 'GET',
            'callback'            => [$this, 'export_logs'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/queue', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_queue'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'add_to_queue'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => [
                    'post_id' => [
                        'type'              => 'integer',
                        'required'          => true,
                        'validate_callback' => static fn($v) => (bool) get_post((int) $v),
                    ],
                    'scheduled_at' => [
                        'type'     => 'string',
                        'required' => false,
                    ],
                    'preset' => [
                        'type'              => 'string',
                        'required'          => false,
                        'validate_callback' => static fn($v) => $v === '' || array_key_exists((string) $v, BotPress_Queue_Manager::presets()),
                    ],
                    'target' => [
                        'type'              => 'string',
                        'required'          => false,
                        'validate_callback' => static fn($v) => in_array($v, ['wordpress', 'channel', 'both'], true),
                    ],
                    'channel_id' => [
                        'required' => false,
                        'validate_callback' => static fn($v) => $v === null || $v === '' || (int) $v > 0,
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/queue/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'cancel_queue_item'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => [
                'id' => ['type' => 'integer', 'required' => true, 'validate_callback' => static fn($v) => (int) $v > 0],
            ],
        ]);

        register_rest_route($this->namespace, '/queue/(?P<id>\d+)/retry', [
            'methods'             => 'POST',
            'callback'            => [$this, 'retry_queue_item'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => [
                'id' => ['type' => 'integer', 'required' => true, 'validate_callback' => static fn($v) => (int) $v > 0],
            ],
        ]);

        register_rest_route($this->namespace, '/posts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/schedule/presets', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_schedule_presets'],
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
                'args'                => [
                    'default'  => ['type' => 'string', 'required' => false, 'validate_callback' => [$this, 'validate_template_string']],
                    'telegram' => ['type' => 'string', 'required' => false, 'validate_callback' => [$this, 'validate_template_string']],
                    'bale'     => ['type' => 'string', 'required' => false, 'validate_callback' => [$this, 'validate_template_string']],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/templates/variables', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_template_variables'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/templates/preview', [
            'methods'             => 'POST',
            'callback'            => [$this, 'preview_template'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/posts/(?P<id>\d+)/publish', [
            'methods'             => 'POST',
            'callback'            => [$this, 'publish_post'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'validate_callback' => static fn($v) => (bool) get_post((int) $v),
                ],
                'target' => [
                    'type'              => 'string',
                    'required'          => false,
                    'validate_callback' => static fn($v) => in_array($v, ['wordpress', 'channel', 'both'], true),
                ],
                'channel_id' => [
                    'required'          => false,
                    'validate_callback' => static fn($v) => $v === null || $v === '' || (int) $v > 0,
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/settings/test-bot', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_bot'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/webhook/set', [
            'methods'             => 'POST',
            'callback'            => [$this, 'set_webhook'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/webhook/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'webhook_status'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/bot/debug-log', [
            [
                'methods'             => 'GET',
                'callback'            => static fn(WP_REST_Request $r) => new WP_REST_Response([
                    'entries' => BotPress_Debug_Log::all(sanitize_text_field((string) $r->get_param('platform'))),
                ], 200),
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => static function () {
                    BotPress_Debug_Log::clear();
                    return new WP_REST_Response(['success' => true], 200);
                },
                'permission_callback' => [$this, 'check_permission'],
            ],
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

    public function check_permission(?WP_REST_Request $request = null): bool {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return false;
        }

        if ($request instanceof WP_REST_Request && in_array($request->get_method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return false;
            }
        }

        return true;
    }

    private function channel_id_arg(): array {
        return [
            'id' => [
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => static function ($v) {
                    global $wpdb;
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}botpress_channels WHERE id = %d", (int) $v
                    ));
                    return (bool) $exists;
                },
            ],
        ];
    }

    private function channel_args(bool $required = true): array {
        return [
            'name' => [
                'type'              => 'string',
                'required'          => $required,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => static fn($v) => $v === null || (strlen((string) $v) > 0 && strlen((string) $v) <= 191),
            ],
            'platform' => [
                'type'              => 'string',
                'required'          => $required,
                'validate_callback' => static fn($v) => $v === null || in_array($v, ['telegram', 'bale'], true),
            ],
            'chat_id' => [
                'type'              => 'string',
                'required'          => $required,
                'validate_callback' => static fn($v) => $v === null || (strlen((string) $v) > 0 && strlen((string) $v) <= 191),
            ],
            'bot_token' => [
                'type'              => 'string',
                'required'          => $required,
                'validate_callback' => fn($v) => $v === null || $this->validate_bot_token($v),
            ],
            'is_active' => [
                'required' => false,
            ],
        ];
    }

    public function validate_bot_token($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }
        if (!preg_match('/^\d+:[A-Za-z0-9_\-]+$/', $value)) {
            return new WP_Error('invalid_token_format', 'فرمت توکن نامعتبر است. توکن باید به شکل 123456789:ABCdef... باشد.', ['status' => 400]);
        }
        return true;
    }

    public function validate_template_string($value): bool {
        $value = (string) $value;
        if (strlen($value) > 4096) {
            return false;
        }
        if (preg_match('/<script\b/i', $value)) {
            return false;
        }
        return true;
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
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'published' AND DATE(published_at) = %s", current_time('Y-m-d'))
        );

        $pending_queue = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'pending'"
        );

        $failed_today = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'failed' AND DATE(updated_at) = %s", current_time('Y-m-d'))
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
            $channel->name = esc_html($channel->name);
            $channel->last_error = $channel->last_error !== null ? esc_html($channel->last_error) : null;
        }

        return new WP_REST_Response(['channels' => $channels], 200);
    }

    public function create_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $name = sanitize_text_field((string) $request->get_param('name'));
        $platform = sanitize_text_field((string) $request->get_param('platform'));
        $chat_id_input = sanitize_text_field((string) $request->get_param('chat_id'));
        $bot_token = trim((string) $request->get_param('bot_token'));

        if (!$name || !in_array($platform, ['telegram', 'bale'], true) || !$chat_id_input || !$bot_token) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_params'], 400);
        }

        $resolved = BotPress_Chat_Id_Resolver::resolve($chat_id_input, $platform, $bot_token);
        if (!$resolved['success']) {
            return new WP_REST_Response(['success' => false, 'message' => $resolved['message'] ?? 'invalid_chat_id'], 400);
        }

        $now = current_time('mysql');

        $wpdb->insert($wpdb->prefix . 'botpress_channels', [
            'name'          => $name,
            'platform'      => $platform,
            'chat_id'       => $resolved['chat_id'],
            'bot_username'  => $resolved['bot_username'] ?? null,
            'bot_token_enc' => BotPress_Encryption::encrypt($bot_token),
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return new WP_REST_Response([
            'success'  => true,
            'id'       => $wpdb->insert_id,
            'chat_id'  => $resolved['chat_id'],
            'resolved' => $resolved['resolved'] ?? false,
            'notice'   => $resolved['message'] ?? null,
        ], 200);
    }

    public function update_channel(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');

        $data = ['updated_at' => current_time('mysql')];
        $notice = null;

        if ($request->get_param('name') !== null) {
            $data['name'] = sanitize_text_field((string) $request->get_param('name'));
        }

        if ($request->get_param('chat_id') !== null) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT platform, bot_token_enc FROM {$wpdb->prefix}botpress_channels WHERE id = %d", $id
            ));
            $platform = (string) ($request->get_param('platform') ?? ($existing->platform ?? 'telegram'));
            $token = $request->get_param('bot_token')
                ? trim((string) $request->get_param('bot_token'))
                : ($existing ? BotPress_Encryption::decrypt($existing->bot_token_enc) : '');

            $resolved = BotPress_Chat_Id_Resolver::resolve(
                sanitize_text_field((string) $request->get_param('chat_id')),
                $platform,
                $token
            );

            if (!$resolved['success']) {
                return new WP_REST_Response(['success' => false, 'message' => $resolved['message'] ?? 'invalid_chat_id'], 400);
            }

            $data['chat_id'] = $resolved['chat_id'];
            if (!empty($resolved['bot_username'])) {
                $data['bot_username'] = $resolved['bot_username'];
            }
            $notice = $resolved['message'] ?? null;
        }

        if ($request->get_param('is_active') !== null) {
            $data['is_active'] = $request->get_param('is_active') ? 1 : 0;
        }
        if ($request->get_param('bot_token')) {
            $data['bot_token_enc'] = BotPress_Encryption::encrypt(trim((string) $request->get_param('bot_token')));
        }

        $wpdb->update($wpdb->prefix . 'botpress_channels', $data, ['id' => $id]);

        return new WP_REST_Response(['success' => true, 'id' => $id, 'notice' => $notice], 200);
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

    public function test_bot(WP_REST_Request $request): WP_REST_Response {
        $platform = (string) $request->get_param('platform');
        if (!in_array($platform, ['telegram', 'bale'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_platform'], 400);
        }

        $driver = BotPress_Driver_Factory::make($platform);
        if (!$driver) {
            return new WP_REST_Response(['success' => false, 'message' => 'no_token'], 200);
        }

        $result = $driver->get_me();
        $success = $result['ok'] ?? false;

        return new WP_REST_Response([
            'success'      => $success,
            'bot_username' => $result['result']['username'] ?? null,
            'message'      => $success ? null : ($result['description'] ?? 'unknown_error'),
        ], 200);
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
        foreach (['telegram', 'bale'] as $platform) {
            $token = trim((string) $request->get_param("{$platform}_token"));
            if ($token === '') {
                continue;
            }
            $previous = BotPress_Encryption::decrypt((string) get_option("botpress_bot_token_{$platform}_enc", ''));
            update_option("botpress_bot_token_{$platform}_enc", BotPress_Encryption::encrypt($token));
            if ($previous !== $token) {
                update_option("botpress_webhook_set_{$platform}", false);
            }
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

        $where = [];
        $values = [];

        $platform = (string) $request->get_param('platform');
        if ($platform !== '') {
            $where[] = 'platform = %s';
            $values[] = $platform;
        }

        $status = (string) $request->get_param('status');
        if ($status !== '') {
            $where[] = 'status = %s';
            $values[] = $status;
        }

        $action = (string) $request->get_param('action');
        if ($action !== '') {
            $where[] = 'action = %s';
            $values[] = $action;
        }

        $search = (string) $request->get_param('search');
        if ($search !== '') {
            $where[] = 'message LIKE %s';
            $values[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $from = (string) $request->get_param('from');
        if ($from !== '') {
            $where[] = 'created_at >= %s';
            $values[] = $from . ' 00:00:00';
        }

        $to = (string) $request->get_param('to');
        if ($to !== '') {
            $where[] = 'created_at <= %s';
            $values[] = $to . ' 23:59:59';
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $per_page = max(1, min(100, (int) ($request->get_param('per_page') ?: 20)));
        $page = max(1, (int) ($request->get_param('page') ?: 1));
        $offset = ($page - 1) * $per_page;

        $logs_sql = "SELECT * FROM {$wpdb->prefix}botpress_logs {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_logs {$where_sql}";

        $logs = $wpdb->get_results($wpdb->prepare($logs_sql, array_merge($values, [$per_page, $offset])));
        $total = (int) $wpdb->get_var($values ? $wpdb->prepare($count_sql, $values) : $count_sql);

        return new WP_REST_Response([
            'logs'     => $logs,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
        ], 200);
    }

    public function clear_logs(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}botpress_logs");
        return new WP_REST_Response(['success' => true], 200);
    }

    public function export_logs(WP_REST_Request $request) {
        global $wpdb;

        $where = [];
        $values = [];

        $platform = (string) $request->get_param('platform');
        if ($platform !== '') {
            $where[] = 'platform = %s';
            $values[] = $platform;
        }

        $status = (string) $request->get_param('status');
        if ($status !== '') {
            $where[] = 'status = %s';
            $values[] = $status;
        }

        $from = (string) $request->get_param('from');
        if ($from !== '') {
            $where[] = 'created_at >= %s';
            $values[] = $from . ' 00:00:00';
        }

        $to = (string) $request->get_param('to');
        if ($to !== '') {
            $where[] = 'created_at <= %s';
            $values[] = $to . ' 23:59:59';
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT created_at, action, platform, status, message FROM {$wpdb->prefix}botpress_logs {$where_sql} ORDER BY id DESC";
        $rows = $wpdb->get_results($values ? $wpdb->prepare($sql, $values) : $sql);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Date', 'Action', 'Platform', 'Status', 'Message']);
        foreach ($rows as $row) {
            fputcsv($handle, [$row->created_at, $row->action, $row->platform, $row->status, $row->message]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="botpress-logs.csv"');
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }

    public function get_queue(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $items = $wpdb->get_results(
            "SELECT q.*, p.post_title
             FROM {$wpdb->prefix}botpress_publish_queue q
             LEFT JOIN {$wpdb->prefix}posts p ON p.ID = q.post_id
             ORDER BY q.scheduled_at DESC
             LIMIT 100"
        );
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue");

        // Vue escapes on render; sending entity-encoded text would display "&amp;" literally.
        foreach ($items as $item) {
            if (isset($item->post_title)) {
                $item->post_title = html_entity_decode((string) $item->post_title, ENT_QUOTES, 'UTF-8');
            }
        }

        return new WP_REST_Response(['items' => $items, 'total' => $total], 200);
    }

    public function add_to_queue(WP_REST_Request $request): WP_REST_Response {
        $post_id = (int) $request->get_param('post_id');
        $preset = (string) $request->get_param('preset');
        $scheduled_at = $preset !== ''
            ? (string) BotPress_Queue_Manager::preset_time($preset)
            : (string) $request->get_param('scheduled_at');
        $target = (string) ($request->get_param('target') ?: 'both');
        $channel_id = $request->get_param('channel_id');
        $channel_id = $channel_id ? (int) $channel_id : null;

        $post = $post_id ? get_post($post_id) : null;
        if (!$post) {
            return new WP_REST_Response(['success' => false, 'message' => 'مقاله یافت نشد.'], 404);
        }

        if ($scheduled_at === '' || strtotime($scheduled_at) === false) {
            return new WP_REST_Response(['success' => false, 'message' => 'زمان انتشار نامعتبر است.'], 400);
        }
        $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));

        if (!BotPress_Queue_Manager::is_future($scheduled_at)) {
            return new WP_REST_Response(['success' => false, 'message' => 'زمان انتخاب‌شده گذشته است. یک زمان در آینده انتخاب کنید.'], 400);
        }

        if (!in_array($target, ['wordpress', 'channel', 'both'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'مقصد انتشار نامعتبر است.'], 400);
        }

        $queue_id = (new BotPress_Queue_Manager())->add($post_id, $scheduled_at, $target, $channel_id);

        if (!$queue_id) {
            return new WP_REST_Response(['success' => false, 'message' => 'ثبت در صف انتشار ناموفق بود.'], 500);
        }

        BotPress_Notifier::scheduled($post, $scheduled_at, (int) $queue_id, 'panel');

        return new WP_REST_Response([
            'success'      => true,
            'id'           => $queue_id,
            'scheduled_at' => $scheduled_at,
            'message'      => 'زمان‌بندی برای ' . mysql2date('Y/m/d H:i', $scheduled_at) . ' ثبت شد.',
        ], 200);
    }

    public function cancel_queue_item(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}botpress_publish_queue WHERE id = %d", $id
        ));
        $success = (new BotPress_Queue_Manager())->cancel($id);
        if ($success && $item) {
            BotPress_Notifier::cancelled($item, 'panel');
        }
        return new WP_REST_Response([
            'success' => $success,
            'message' => $success ? 'زمان‌بندی لغو شد.' : 'این مورد دیگر در انتظار نیست (منتشر یا لغو شده).',
        ], $success ? 200 : 400);
    }

    public function retry_queue_item(WP_REST_Request $request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $success = (new BotPress_Queue_Manager())->retry($id);
        return new WP_REST_Response(['success' => $success], $success ? 200 : 400);
    }

    public function get_posts(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $status_map = ['draft' => ['draft'], 'publish' => ['publish'], 'future' => ['future'], 'pending' => ['pending']];
        $status = (string) $request->get_param('status');
        $per_page = max(1, min(50, (int) ($request->get_param('per_page') ?: 20)));
        $page = max(1, (int) ($request->get_param('page') ?: 1));

        $query = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => $status_map[$status] ?? ['draft', 'publish', 'future', 'pending'],
            's'              => sanitize_text_field((string) $request->get_param('search')),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $ids = wp_list_pluck($query->posts, 'ID');
        $queued = [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, post_id, scheduled_at, publish_target FROM {$wpdb->prefix}botpress_publish_queue
                 WHERE status = 'pending' AND post_id IN ({$placeholders}) ORDER BY scheduled_at ASC",
                ...$ids
            ));
            foreach ($rows as $row) {
                $queued[(int) $row->post_id] ??= $row;
            }
        }

        $data = array_map(static function (WP_Post $post) use ($queued) {
            $q = $queued[$post->ID] ?? null;
            return [
                'id'         => $post->ID,
                'title'      => html_entity_decode($post->post_title ?: '(بدون عنوان)', ENT_QUOTES, 'UTF-8'),
                'status'     => $post->post_status,
                'date'       => $post->post_date,
                'modified'   => $post->post_modified,
                'author'     => get_the_author_meta('display_name', $post->post_author),
                'thumbnail'  => get_the_post_thumbnail_url($post, 'thumbnail') ?: null,
                'edit_url'   => get_edit_post_link($post->ID, 'raw'),
                'view_url'   => get_permalink($post),
                'queue'      => $q ? [
                    'id'           => (int) $q->id,
                    'scheduled_at' => $q->scheduled_at,
                    'target'       => $q->publish_target,
                ] : null,
            ];
        }, $query->posts);

        return new WP_REST_Response([
            'posts'       => $data,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => $page,
        ], 200);
    }

    public function get_schedule_presets(WP_REST_Request $request): WP_REST_Response {
        $presets = [];
        foreach (BotPress_Queue_Manager::presets() as $key => $label) {
            $presets[] = ['key' => $key, 'label' => $label, 'at' => BotPress_Queue_Manager::preset_time($key)];
        }
        return new WP_REST_Response([
            'presets'  => $presets,
            'now'      => current_time('mysql'),
            'timezone' => wp_timezone_string(),
        ], 200);
    }

    public function get_templates(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(
            array_merge(
                BotPress_Template_Engine::get_templates(),
                ['variables' => BotPress_Template_Engine::available_variables()]
            ),
            200
        );
    }

    public function save_template(WP_REST_Request $request): WP_REST_Response {
        BotPress_Template_Engine::save_templates([
            'default'  => (string) $request->get_param('default'),
            'telegram' => (string) $request->get_param('telegram'),
            'bale'     => (string) $request->get_param('bale'),
        ]);
        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_template_variables(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['variables' => BotPress_Template_Engine::available_variables()], 200);
    }

    public function preview_template(WP_REST_Request $request): WP_REST_Response {
        $template = (string) $request->get_param('template');
        return new WP_REST_Response(['preview' => BotPress_Template_Engine::preview($template)], 200);
    }

    public function publish_post(WP_REST_Request $request): WP_REST_Response {
        $post_id = (int) $request->get_param('id');
        $target = (string) ($request->get_param('target') ?: 'both');
        $channel_id = $request->get_param('channel_id');
        $channel_id = $channel_id ? (int) $channel_id : null;

        if (!in_array($target, ['wordpress', 'channel', 'both'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'invalid_target'], 400);
        }

        $result = (new BotPress_Publisher_Engine())->publish_now($post_id, $target, $channel_id);

        $post = get_post($post_id);
        if ($post) {
            if ($result['success']) {
                (new BotPress_Queue_Manager())->cancel_by_post($post_id, 'منتشر شد (انتشار فوری از پنل)');
                BotPress_Notifier::published($post, $result, 'panel');
            } else {
                BotPress_Notifier::failed($post, BotPress_Cron_Scheduler::first_error($result), 'panel');
            }
        }

        $result['message'] = $result['success'] ? 'انتشار با موفقیت انجام شد.' : BotPress_Cron_Scheduler::first_error($result);

        return new WP_REST_Response($result, 200);
    }

    public function set_webhook(WP_REST_Request $request): WP_REST_Response {
        $platform = sanitize_text_field((string) $request->get_param('platform'));

        if (!in_array($platform, ['telegram', 'bale'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'پلتفرم نامعتبر است.'], 400);
        }

        $driver = BotPress_Driver_Factory::make($platform);
        if (!$driver) {
            return new WP_REST_Response(['success' => false, 'message' => 'ابتدا توکن ربات را ذخیره کنید.'], 200);
        }

        $url = $this->webhook_url($platform);
        if (strpos($url, 'https://') !== 0) {
            update_option("botpress_webhook_set_{$platform}", false);
            return new WP_REST_Response([
                'success' => false,
                'message' => 'آدرس وب‌هوک باید HTTPS باشد. آدرس فعلی: ' . $url,
            ], 200);
        }

        $me = $driver->get_me();
        if (!($me['ok'] ?? false)) {
            update_option("botpress_webhook_set_{$platform}", false);
            return new WP_REST_Response([
                'success' => false,
                'message' => 'توکن نامعتبر است: ' . ($me['description'] ?? 'unknown_error'),
            ], 200);
        }

        $secret = $platform === 'telegram' ? (string) get_option('botpress_webhook_secret', '') : '';
        $result = $driver->set_webhook($url, $secret);

        if (!($result['ok'] ?? false)) {
            update_option("botpress_webhook_set_{$platform}", false);
            return new WP_REST_Response([
                'success' => false,
                'message' => 'تنظیم وب‌هوک ناموفق بود: ' . ($result['description'] ?? 'unknown_error'),
            ], 200);
        }

        update_option("botpress_webhook_set_{$platform}", true);

        return new WP_REST_Response([
            'success'      => true,
            'message'      => 'وب‌هوک با موفقیت تنظیم شد.',
            'bot_username' => $me['result']['username'] ?? null,
            'webhook'      => $this->live_webhook_status($driver, $platform),
        ], 200);
    }

    public function webhook_status(WP_REST_Request $request): WP_REST_Response {
        $platform = sanitize_text_field((string) $request->get_param('platform'));
        if (!in_array($platform, ['telegram', 'bale'], true)) {
            return new WP_REST_Response(['success' => false, 'message' => 'پلتفرم نامعتبر است.'], 400);
        }

        $driver = BotPress_Driver_Factory::make($platform);
        if (!$driver) {
            return new WP_REST_Response(['success' => false, 'message' => 'ابتدا توکن ربات را ذخیره کنید.'], 200);
        }

        $status = $this->live_webhook_status($driver, $platform);
        if ($status['checked']) {
            update_option("botpress_webhook_set_{$platform}", $status['matches']);
        }

        return new WP_REST_Response(['success' => true, 'webhook' => $status], 200);
    }

    private function webhook_url(string $platform): string {
        return rest_url("botpress/v1/webhook/{$platform}");
    }

    private function live_webhook_status(BotPress_Bot_Driver_Interface $driver, string $platform): array {
        $expected = $this->webhook_url($platform);
        $info = $driver->get_webhook_info();
        $last_hit = (int) get_option("botpress_webhook_last_hit_{$platform}", 0);

        if (!($info['ok'] ?? false)) {
            return [
                'checked'            => false,
                'matches'            => (bool) get_option("botpress_webhook_set_{$platform}", false),
                'expected_url'       => $expected,
                'registered_url'     => null,
                'pending_updates'    => null,
                'last_error_message' => $info['description'] ?? null,
                'last_received_at'   => $last_hit ? gmdate('c', $last_hit) : null,
            ];
        }

        $result = $info['result'] ?? [];
        $registered = (string) ($result['url'] ?? '');
        $last_error_date = (int) ($result['last_error_date'] ?? 0);

        return [
            'checked'            => true,
            'matches'            => $registered !== '' && untrailingslashit($registered) === untrailingslashit($expected),
            'expected_url'       => $expected,
            'registered_url'     => $registered ?: null,
            'pending_updates'    => isset($result['pending_update_count']) ? (int) $result['pending_update_count'] : null,
            'last_error_message' => $result['last_error_message'] ?? null,
            'last_error_at'      => $last_error_date ? gmdate('c', $last_error_date) : null,
            'last_received_at'   => $last_hit ? gmdate('c', $last_hit) : null,
        ];
    }
}
