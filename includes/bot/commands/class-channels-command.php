<?php

defined('ABSPATH') || exit;

class BotPress_Channels_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        global $wpdb;

        $channels = $wpdb->get_results(
            "SELECT name, platform, chat_id, is_active FROM {$wpdb->prefix}botpress_channels ORDER BY id DESC"
        );

        if (empty($channels)) {
            return $this->reply($context, '📢 هیچ کانالی تنظیم نشده است.');
        }

        $text = "📢 <b>کانال‌های متصل</b>\n\n";
        foreach ($channels as $channel) {
            $status_icon = $channel->is_active ? '✅' : '❌';
            $text .= "• " . esc_html($channel->name) . " ({$channel->platform}) — {$channel->chat_id} {$status_icon}\n";
        }

        return $this->reply($context, $text);
    }
}
