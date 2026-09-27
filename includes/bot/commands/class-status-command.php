<?php

defined('ABSPATH') || exit;

class BotPress_Status_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        global $wpdb;

        $total_posts = wp_count_posts('post')->publish
            + wp_count_posts('post')->draft
            + wp_count_posts('post')->future;

        $pending = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'pending'"
        );

        $published_today = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'published' AND DATE(published_at) = %s", current_time('Y-m-d'))
        );

        $failed_today = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'failed' AND DATE(updated_at) = %s", current_time('Y-m-d'))
        );

        $channels = $wpdb->get_results(
            "SELECT name, platform, is_active FROM {$wpdb->prefix}botpress_channels ORDER BY id DESC"
        );

        $text = "📊 *وضعیت سیستم*\n\n"
            . "🌐 سایت: " . $this->md(get_site_url()) . "\n"
            . "📝 کل مقالات: {$total_posts}\n"
            . "📅 در صف: {$pending}\n"
            . "✅ منتشرشده امروز: {$published_today}\n"
            . "❌ ناموفق: {$failed_today}\n\n"
            . "📢 کانال‌های متصل:\n";

        if (empty($channels)) {
            $text .= "— هیچ کانالی متصل نیست";
        } else {
            foreach ($channels as $channel) {
                $status_icon = $channel->is_active ? '✅' : '❌';
                $text .= "• " . $this->md($channel->name) . " ({$channel->platform}) {$status_icon}\n";
            }
        }

        return $this->reply($context, $text);
    }
}
