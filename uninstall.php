<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$tables = [
    $wpdb->prefix . 'botpress_channels',
    $wpdb->prefix . 'botpress_publish_queue',
    $wpdb->prefix . 'botpress_logs',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

$options = [
    'botpress_db_version',
    'botpress_bot_token_telegram_enc',
    'botpress_bot_token_bale_enc',
    'botpress_webhook_secret',
    'botpress_authorized_users',
    'botpress_default_template',
    'botpress_notify_on_publish',
    'botpress_notify_on_fail',
];

foreach ($options as $option) {
    delete_option($option);
}

wp_clear_scheduled_hook('botpress_process_queue');
