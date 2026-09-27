<?php

defined('ABSPATH') || exit;

class BotPress_AI_REST {
    private string $namespace = 'botpress/v1';
    private BotPress_REST_API $core;

    public function __construct(BotPress_REST_API $core) {
        $this->core = $core;
    }

    public function register_routes(): void {
        register_rest_route($this->namespace, '/ai/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this->core, 'check_permission'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'save_settings'],
                'permission_callback' => [$this->core, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/ai/account', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_account'],
            'permission_callback' => [$this->core, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/ai/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_connection'],
            'permission_callback' => [$this->core, 'check_permission'],
        ]);
    }

    public function get_settings(): WP_REST_Response {
        return rest_ensure_response(BotPress_AI_Settings::public_view());
    }

    public function save_settings(WP_REST_Request $request) {
        $values = [];

        $mode = $request->get_param('mode');
        if ($mode !== null) {
            if (!in_array($mode, ['server', 'byok'], true)) {
                return new WP_Error('botpress_invalid_mode', 'حالت اتصال نامعتبر است.', ['status' => 400]);
            }
            $values['mode'] = $mode;
        }

        foreach (['server_url', 'byok_base_url'] as $key) {
            $url = $request->get_param($key);
            if ($url === null) {
                continue;
            }
            $url = trim((string) $url);
            if ($url !== '' && !wp_http_validate_url($url)) {
                return new WP_Error('botpress_invalid_url', 'آدرس واردشده معتبر نیست.', ['status' => 400]);
            }
            $values[$key] = esc_url_raw($url);
        }

        $license = $request->get_param('license_key');
        if (is_string($license) && trim($license) !== '') {
            $license = strtoupper(trim($license));
            if (!preg_match('/^BPAI(-[A-F0-9]{5}){4}$/', $license)) {
                return new WP_Error('botpress_invalid_license', 'قالب کلید لایسنس درست نیست (مثل BPAI-XXXXX-XXXXX-XXXXX-XXXXX).', ['status' => 400]);
            }
            $values['license_key_enc'] = BotPress_Encryption::encrypt($license);
        }

        $byok_key = $request->get_param('byok_key');
        if (is_string($byok_key) && trim($byok_key) !== '') {
            $values['byok_key_enc'] = BotPress_Encryption::encrypt(trim($byok_key));
        }

        $auth = $request->get_param('byok_auth');
        if ($auth !== null) {
            $values['byok_auth'] = $auth === 'bearer' ? 'bearer' : 'apikey';
        }

        $model = $request->get_param('byok_model');
        if ($model !== null) {
            $values['byok_model'] = sanitize_text_field((string) $model);
        }

        BotPress_AI_Settings::update($values);
        return rest_ensure_response(BotPress_AI_Settings::public_view());
    }

    public function get_account() {
        $settings = BotPress_AI_Settings::all();
        if ($settings['mode'] !== 'server') {
            return rest_ensure_response(['mode' => 'byok']);
        }
        $account = BotPress_AI_Settings::server_account();
        if (is_wp_error($account)) {
            return rest_ensure_response(['mode' => 'server', 'connected' => false, 'error' => $account->get_error_message()]);
        }
        return rest_ensure_response(['mode' => 'server', 'connected' => true] + $account);
    }

    public function test_connection() {
        $settings = BotPress_AI_Settings::all();
        $result = $settings['mode'] === 'server' ? BotPress_AI_Settings::server_account() : BotPress_AI_Settings::test_byok();
        if (is_wp_error($result)) {
            return rest_ensure_response(['success' => false, 'message' => $result->get_error_message()]);
        }
        return rest_ensure_response(['success' => true, 'mode' => $settings['mode'], 'result' => $result]);
    }
}
