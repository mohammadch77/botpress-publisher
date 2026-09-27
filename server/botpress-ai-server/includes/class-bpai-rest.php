<?php

defined('ABSPATH') || exit;

/**
 * Public API consumed by BotPress Publisher installs. Every call is authenticated by
 * license key + site URL headers; no provider secrets ever leave this server.
 */
class BPAI_REST {
    public const NAMESPACE = 'bpai/v1';

    public static function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/account', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'account'],
            'permission_callback' => '__return_true',
        ]);
    }

    /** @return array|WP_Error */
    public static function authenticate(WP_REST_Request $request) {
        return BPAI_Licenses::authenticate(
            (string) $request->get_header('x-bpai-license'),
            (string) $request->get_header('x-bpai-site')
        );
    }

    public static function account(WP_REST_Request $request) {
        $license = self::authenticate($request);
        if (is_wp_error($license)) {
            return $license;
        }
        return rest_ensure_response([
            'customer'        => $license['customer_name'],
            'site'            => $license['site_url'],
            'status'          => $license['status'],
            'credits'         => (int) $license['credits'],
            'pro_credit_cost' => (int) BPAI_Settings::get('pro_credit_cost', 2),
            'server_version'  => BPAI_VERSION,
            'features'        => ['article_generation' => false],
        ]);
    }
}
