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
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_api']);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
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
            'BotPress Publisher',
            'BotPress',
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

    public function register_api(): void {
        (new BotPress_REST_API())->register_routes();
    }
}
