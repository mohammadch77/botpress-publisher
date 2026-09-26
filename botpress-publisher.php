<?php
/**
 * Plugin Name: BotPress Publisher
 * Plugin URI: https://github.com/mohammadch77/botpress-publisher
 * Description: Publish WordPress posts to Telegram and Bale channels via Bot
 * Version: 1.0.0
 * Author: Mohammad
 * License: GPL v2 or later
 * Text Domain: botpress-publisher
 */

defined('ABSPATH') || exit;

define('BOTPRESS_VERSION', '1.0.0');
define('BOTPRESS_FILE', __FILE__);
define('BOTPRESS_PATH', plugin_dir_path(__FILE__));
define('BOTPRESS_URL', plugin_dir_url(__FILE__));
define('BOTPRESS_ADMIN_URL', BOTPRESS_URL . 'admin/');

require_once BOTPRESS_PATH . 'includes/class-plugin.php';
require_once BOTPRESS_PATH . 'includes/class-error-handler.php';
require_once BOTPRESS_PATH . 'includes/class-activator.php';
require_once BOTPRESS_PATH . 'includes/class-deactivator.php';
require_once BOTPRESS_PATH . 'includes/helpers/class-encryption.php';
require_once BOTPRESS_PATH . 'includes/helpers/class-template-engine.php';
require_once BOTPRESS_PATH . 'includes/api/class-rest-api.php';
require_once BOTPRESS_PATH . 'includes/bot/interface-bot-driver.php';
require_once BOTPRESS_PATH . 'includes/bot/class-telegram-driver.php';
require_once BOTPRESS_PATH . 'includes/bot/class-bale-driver.php';
require_once BOTPRESS_PATH . 'includes/bot/class-driver-factory.php';
require_once BOTPRESS_PATH . 'includes/bot/class-webhook-handler.php';
require_once BOTPRESS_PATH . 'includes/bot/class-command-router.php';
require_once BOTPRESS_PATH . 'includes/bot/class-message-builder.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-base-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-start-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-posts-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-search-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-post-detail-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-schedule-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-publish-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-channels-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-status-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-help-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-pending-command.php';
require_once BOTPRESS_PATH . 'includes/bot/commands/class-cancel-command.php';
require_once BOTPRESS_PATH . 'includes/publisher/class-publisher-engine.php';
require_once BOTPRESS_PATH . 'includes/publisher/class-wordpress-publisher.php';
require_once BOTPRESS_PATH . 'includes/publisher/class-channel-publisher.php';
require_once BOTPRESS_PATH . 'includes/scheduler/class-queue-manager.php';
require_once BOTPRESS_PATH . 'includes/scheduler/class-cron-scheduler.php';

register_activation_hook(__FILE__, ['BotPress_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['BotPress_Deactivator', 'deactivate']);

BotPress_Plugin::instance()->init();
