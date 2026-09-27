<?php

defined('ABSPATH') || exit;

/** Pushes publish/schedule events to everyone who uses the bot, so panel and bot stay in sync. */
class BotPress_Notifier {
    private const SUBSCRIBERS_OPTION = 'botpress_bot_subscribers';
    private const MAX_PER_PLATFORM = 50;

    public static function remember_chat(string $platform, string $chat_id): void {
        if ($chat_id === '' || $chat_id === '0') {
            return;
        }
        $all = get_option(self::SUBSCRIBERS_OPTION, []);
        $list = is_array($all[$platform] ?? null) ? $all[$platform] : [];
        if (in_array($chat_id, $list, true)) {
            return;
        }
        array_unshift($list, $chat_id);
        $all[$platform] = array_slice($list, 0, self::MAX_PER_PLATFORM);
        update_option(self::SUBSCRIBERS_OPTION, $all, false);
    }

    public static function published(WP_Post $post, array $result, string $source): void {
        if (!get_option('botpress_notify_on_publish', true)) {
            return;
        }
        $ok = count(array_filter($result['channels'] ?? [], static fn($c) => !empty($c['success'])));
        $text = '✅ *انتشار انجام شد* ' . self::source_label($source) . "\n\n"
            . '📄 ' . BotPress_Markdown::bold($post->post_title) . "\n";
        if ($ok > 0) {
            $text .= "📢 ارسال به {$ok} کانال\n";
        }
        $url = $result['wordpress']['url'] ?? get_permalink($post);
        if ($url) {
            $text .= BotPress_Markdown::link('🔗 مشاهده مقاله', (string) $url);
        }
        self::broadcast($text);
    }

    public static function failed(WP_Post $post, string $error, string $source): void {
        if (!get_option('botpress_notify_on_fail', true)) {
            return;
        }
        $text = '❌ *انتشار ناموفق* ' . self::source_label($source) . "\n\n"
            . '📄 ' . BotPress_Markdown::bold($post->post_title) . "\n"
            . '⚠️ ' . BotPress_Markdown::escape($error);
        self::broadcast($text);
    }

    public static function scheduled(WP_Post $post, string $scheduled_at, int $queue_id, string $source): void {
        if (!get_option('botpress_notify_on_publish', true)) {
            return;
        }
        $text = '📅 *زمان‌بندی ثبت شد* ' . self::source_label($source) . "\n\n"
            . '📄 ' . BotPress_Markdown::bold($post->post_title) . "\n"
            . '🕒 ' . mysql2date('Y/m/d H:i', $scheduled_at) . "\n\n"
            . "برای لغو: `/cancel {$queue_id}`";
        self::broadcast($text);
    }

    public static function cancelled(object $item, string $source): void {
        if (!get_option('botpress_notify_on_publish', true)) {
            return;
        }
        $post = get_post((int) $item->post_id);
        $text = '🚫 *زمان‌بندی لغو شد* ' . self::source_label($source) . "\n\n"
            . '📄 ' . BotPress_Markdown::bold($post ? $post->post_title : ('#' . $item->post_id));
        self::broadcast($text);
    }

    private static function source_label(string $source): string {
        return [
            'panel' => '(از پنل وردپرس)',
            'queue' => '(صف زمان‌بندی)',
            'bot'   => '(از ربات)',
        ][$source] ?? '';
    }

    private static function broadcast(string $text): void {
        $all = get_option(self::SUBSCRIBERS_OPTION, []);
        $authorized = array_map('strval', (array) get_option('botpress_authorized_users', []));

        foreach (['telegram', 'bale'] as $platform) {
            $chats = is_array($all[$platform] ?? null) ? $all[$platform] : [];
            if ($authorized) {
                $chats = array_values(array_intersect($chats, $authorized));
            }
            if (!$chats) {
                continue;
            }
            $driver = BotPress_Driver_Factory::make($platform);
            if (!$driver) {
                continue;
            }
            foreach ($chats as $chat_id) {
                $driver->send_message((string) $chat_id, $text);
            }
        }
    }
}
