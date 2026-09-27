<?php

defined('ABSPATH') || exit;

class BPAI_Installer {
    private const SCHEMA_VERSION = 1;

    public static function activate(): void {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(BPAI_FILE));
            wp_die('برای اجرای سرور هوش مصنوعی حداقل PHP 7.4 لازم است.', 'خطا در فعال‌سازی', ['back_link' => true]);
        }
        self::create_tables();
    }

    public static function maybe_upgrade(): void {
        if ((int) get_option('bpai_schema_version', 0) < self::SCHEMA_VERSION) {
            self::create_tables();
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$wpdb->prefix}bpai_licenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            key_hash CHAR(64) NOT NULL,
            key_hint VARCHAR(16) NOT NULL,
            customer_name VARCHAR(191) NOT NULL DEFAULT '',
            customer_contact VARCHAR(191) NOT NULL DEFAULT '',
            site_url VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            credits INT NOT NULL DEFAULT 0,
            note TEXT NULL,
            last_seen_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY key_hash (key_hash)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}bpai_credit_ledger (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id BIGINT UNSIGNED NOT NULL,
            delta INT NOT NULL,
            balance_after INT NOT NULL,
            reason VARCHAR(50) NOT NULL,
            reference VARCHAR(191) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY license_id (license_id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}bpai_usage (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id BIGINT UNSIGNED NULL,
            job_ref VARCHAR(64) NOT NULL DEFAULT '',
            stage VARCHAR(30) NOT NULL,
            provider VARCHAR(30) NOT NULL,
            model VARCHAR(100) NOT NULL DEFAULT '',
            input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
            output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
            cost_toman DECIMAL(14,2) NOT NULL DEFAULT 0,
            duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
            success TINYINT(1) NOT NULL DEFAULT 1,
            error TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY created_at (created_at)
        ) $charset;");

        update_option('bpai_schema_version', self::SCHEMA_VERSION);
    }
}
