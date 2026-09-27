<?php
/**
 * Plugin Name: BotPress AI Server
 * Description: سرور مرکزی تولید مقاله با هوش مصنوعی برای افزونهٔ BotPress Publisher — لایسنس، اعتبار، اتصال به مدل‌های زبانی و DataForSEO.
 * Version: 0.2.0
 * Author: Mohammad
 * License: GPL v2 or later
 * Text Domain: botpress-ai-server
 */

defined('ABSPATH') || exit;

define('BPAI_VERSION', '0.2.0');
define('BPAI_FILE', __FILE__);
define('BPAI_PATH', plugin_dir_path(__FILE__));
define('BPAI_URL', plugin_dir_url(__FILE__));

require_once BPAI_PATH . 'includes/class-bpai-crypto.php';
require_once BPAI_PATH . 'includes/class-bpai-settings.php';
require_once BPAI_PATH . 'includes/class-bpai-installer.php';
require_once BPAI_PATH . 'includes/class-bpai-licenses.php';
require_once BPAI_PATH . 'includes/class-bpai-usage.php';
require_once BPAI_PATH . 'includes/providers/class-bpai-llm-client.php';
require_once BPAI_PATH . 'includes/providers/class-bpai-dataforseo.php';
require_once BPAI_PATH . 'includes/class-bpai-rest.php';
require_once BPAI_PATH . 'includes/admin/class-bpai-admin.php';

register_activation_hook(__FILE__, ['BPAI_Installer', 'activate']);

add_action('plugins_loaded', static function () {
    BPAI_Installer::maybe_upgrade();
    add_action('rest_api_init', [BPAI_REST::class, 'register_routes']);
    if (is_admin()) {
        BPAI_Admin::init();
    }
});
