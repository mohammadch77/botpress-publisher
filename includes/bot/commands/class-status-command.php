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
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'published' AND DATE(published_at) = CURDATE()"
        );

        $failed_today = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}botpress_publish_queue WHERE status = 'failed' AND DATE(updated_at) = CURDATE()"
        );

        $channels = $wpdb->get_results(
            "SELECT name, platform, is_active FROM {$wpdb->prefix}botpress_channels ORDER BY id DESC"
        );

        $text = "📊 <b>وضعیت سیستم</b>\n\n"
            . "🌐 سایت: " . esc_html(get_site_url()) . "\n"
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
                $text .= "• " . esc_html($channel->name) . " ({$channel->platform}) {$status_icon}\n";
            }
        }

        return $this->reply($context, $text);
    }
}
