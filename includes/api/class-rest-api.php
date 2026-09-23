<?php

defined('ABSPATH') || exit;

class BotPress_REST_API {
    private string $namespace = 'botpress/v1';

    public function register_routes(): void {
        $admin_only = [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'permission_callback' => [$this, 'check_permission'],
        ];

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
    }

    public function check_permission(): bool {
        return current_user_can('manage_options');
    }

    public function get_dashboard_stats(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'total_channels'       => 0,
            'published_today'      => 0,
            'pending_in_queue'     => 0,
            'failed_today'         => 0,
            'recent_activity'      => [],
        ], 200);
    }

    public function get_channels(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['channels' => []], 200);
    }

    public function create_channel(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true, 'id' => 0], 200);
    }

    public function update_channel(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true, 'id' => (int) $request->get_param('id')], 200);
    }

    public function delete_channel(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true], 200);
    }

    public function test_channel(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true, 'message' => 'not_implemented'], 200);
    }

    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'telegram' => ['token_masked' => '', 'webhook_url' => '', 'connected' => false],
            'bale'     => ['token_masked' => '', 'webhook_url' => '', 'connected' => false],
            'authorized_users' => get_option('botpress_authorized_users', []),
        ], 200);
    }

    public function save_settings(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_logs(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['logs' => [], 'total' => 0], 200);
    }

    public function clear_logs(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_queue(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['queue' => []], 200);
    }

    public function get_posts(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['posts' => []], 200);
    }

    public function get_templates(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'template'  => get_option('botpress_default_template', ''),
            'variables' => BotPress_Template_Engine::available_variables(),
        ], 200);
    }

    public function save_template(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true], 200);
    }

    public function set_webhook(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response(['success' => true, 'message' => 'not_implemented'], 200);
    }
}
