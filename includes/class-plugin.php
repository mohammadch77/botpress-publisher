<?php

defined('ABSPATH') || exit;

class BotPress_Plugin {
    private static ?BotPress_Plugin $instance = null;

    public static function instance(): BotPress_Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        add_action('init', [BotPress_Activator::class, 'maybe_upgrade'], 1);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_api']);
        add_filter('rest_pre_dispatch', [$this, 'disable_rest_cache'], 10, 3);
        add_filter('rest_post_dispatch', [$this, 'add_no_cache_headers'], 10, 3);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        BotPress_Cron_Scheduler::register();
    }

    public function add_cron_schedules(array $schedules): array {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute', 'botpress-publisher'),
        ];
        return $schedules;
    }

    public function add_menu(): void {
        add_menu_page(
            'بات‌پرس پابلیشر',
            'بات‌پرس',
            'manage_options',
            'botpress-publisher',
            [$this, 'render_admin'],
            'dashicons-share',
            30
        );
    }

    public function render_admin(): void {
        echo '<div id="botpress-app"></div>';
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_botpress-publisher') {
            return;
        }

        $js_path = BOTPRESS_PATH . 'admin/index.js';
        $css_path = BOTPRESS_PATH . 'admin/index.css';

        if (file_exists($js_path)) {
            wp_enqueue_script(
                'botpress-app',
                BOTPRESS_ADMIN_URL . 'index.js',
                [],
                (string) filemtime($js_path),
                true
            );
            add_filter('script_loader_tag', [$this, 'add_module_type'], 10, 2);
        }

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'botpress-app',
                BOTPRESS_ADMIN_URL . 'index.css',
                [],
                (string) filemtime($css_path)
            );
        }

        wp_localize_script('botpress-app', 'botpressData', [
            'apiUrl'  => rest_url('botpress/v1'),
            'nonce'   => wp_create_nonce('wp_rest'),
            'siteUrl' => get_site_url(),
            'version' => BOTPRESS_VERSION,
        ]);
    }

    public function add_module_type(string $tag, string $handle): string {
        if ($handle !== 'botpress-app') {
            return $tag;
        }
        if (strpos($tag, 'type=') !== false) {
            return $tag;
        }
        return str_replace(' src=', ' type="module" src=', $tag);
    }

    private function is_botpress_route(WP_REST_Request $request): bool {
        return strpos($request->get_route(), '/botpress/v1') === 0;
    }

    // Page-cache plugins (LiteSpeed, WP Rocket, ...) may cache REST GETs publicly; admin data must always be fresh.
    public function disable_rest_cache($result, $server, WP_REST_Request $request) {
        if ($this->is_botpress_route($request)) {
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            do_action('litespeed_control_set_nocache', 'botpress admin api');
            add_action('shutdown', [BotPress_Cron_Scheduler::class, 'maybe_process']);
        }
        return $result;
    }

    public function add_no_cache_headers($response, $server, WP_REST_Request $request) {
        if ($response instanceof WP_REST_Response && $this->is_botpress_route($request)) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->header('X-LiteSpeed-Cache-Control', 'no-cache');
            $response->header('Pragma', 'no-cache');
        }
        return $response;
    }

    public function register_api(): void {
        $core = new BotPress_REST_API();
        $core->register_routes();
        (new BotPress_AI_REST($core))->register_routes();
    }
}
