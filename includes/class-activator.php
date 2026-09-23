<?php

defined('ABSPATH') || exit;

class BotPress_Activator {
    public static function activate(): void {
        self::create_tables();
        self::set_default_options();
        self::schedule_cron();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}botpress_channels (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            platform ENUM('telegram','bale') NOT NULL,
            chat_id VARCHAR(191) NOT NULL,
            bot_token_enc TEXT NOT NULL,
            bot_username VARCHAR(191) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_error TEXT NULL,
            last_used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) $charset");

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}botpress_publish_queue (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            channel_id INT UNSIGNED NULL,
            publish_target ENUM('wordpress','channel','both') NOT NULL DEFAULT 'both',
            status ENUM('pending','processing','published','failed') NOT NULL DEFAULT 'pending',
            scheduled_at DATETIME NOT NULL,
            published_at DATETIME NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
            last_error TEXT NULL,
            context JSON NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_status_scheduled (status, scheduled_at),
            INDEX idx_post (post_id)
        ) $charset");

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}botpress_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue_id INT UNSIGNED NULL,
            post_id BIGINT UNSIGNED NULL,
            channel_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            platform ENUM('telegram','bale','wordpress') NULL,
            status ENUM('success','failed','info') NOT NULL,
            message TEXT NULL,
            context JSON NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_post (post_id),
            INDEX idx_created (created_at)
        ) $charset");

        update_option('botpress_db_version', BOTPRESS_VERSION);
    }

    private static function set_default_options(): void {
        add_option('botpress_bot_token_telegram_enc', '');
        add_option('botpress_bot_token_bale_enc', '');
        add_option('botpress_webhook_secret', wp_generate_password(32, false));
        add_option('botpress_authorized_users', []);
        add_option('botpress_default_template', self::default_template());
        add_option('botpress_notify_on_publish', true);
        add_option('botpress_notify_on_fail', true);
    }

    private static function default_template(): string {
        return "📌 <b>{title}</b>\n\n{excerpt}\n\n🔗 <a href=\"{url}\">ادامه مطلب</a>";
    }

    private static function schedule_cron(): void {
        if (!wp_next_scheduled('botpress_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'botpress_process_queue');
        }
    }
}
