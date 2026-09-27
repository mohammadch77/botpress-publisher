<?php

defined('ABSPATH') || exit;

abstract class BotPress_Base_Command {
    protected function reply(array $context, string $text, array $options = []): array {
        return $context['driver']->send_message($context['chat_id'], $text, $options);
    }

    /** Edits the originating message for button presses; sends a new message if editing isn't possible. */
    protected function respond(array $context, string $text, array $options = []): array {
        $message_id = (int) ($context['message_id'] ?? 0);
        // Bale acknowledges editMessageText on keyboard messages but clients don't show the new content, so always send fresh.
        if ($message_id > 0 && $context['driver']->get_platform() !== 'bale') {
            $result = $context['driver']->edit_message($context['chat_id'], $message_id, $text, $options);
            if ($result['ok'] ?? false) {
                BotPress_Debug_Log::add($context['driver']->get_platform(), 'info', 'پیام ویرایش شد (پاسخ دکمه)');
                return $result;
            }
            if (stripos((string) ($result['description'] ?? ''), 'not modified') !== false) {
                return $result;
            }
            BotPress_Debug_Log::add($context['driver']->get_platform(), 'warn', 'ویرایش پیام ناموفق؛ ارسال پیام جدید', (string) ($result['description'] ?? 'unknown'));
        }
        $sent = $this->reply($context, $text, $options);
        BotPress_Debug_Log::add(
            $context['driver']->get_platform(),
            ($sent['ok'] ?? false) ? 'info' : 'error',
            ($sent['ok'] ?? false) ? 'پاسخ ارسال شد' : 'ارسال پاسخ ناموفق',
            ($sent['ok'] ?? false) ? '' : (string) ($sent['description'] ?? 'unknown')
        );
        return $sent;
    }

    protected function inline_keyboard(array $buttons): array {
        return ['reply_markup' => ['inline_keyboard' => $buttons]];
    }

    protected function md(?string $text): string {
        return BotPress_Markdown::escape($text);
    }

    protected function get_post_excerpt(WP_Post $post, int $length = 200): string {
        $excerpt = $post->post_excerpt ?: wp_trim_words(
            wp_strip_all_tags($post->post_content), 30, '...'
        );
        return mb_substr($excerpt, 0, $length);
    }

    protected function format_date(string $date): string {
        return mysql2date('Y/m/d H:i', $date);
    }

    protected function status_label(string $status): string {
        return [
            'publish' => 'منتشرشده',
            'draft'   => 'پیش‌نویس',
            'future'  => 'زمان‌بندی‌شده',
            'pending' => 'در انتظار بررسی',
            'private' => 'خصوصی',
        ][$status] ?? $status;
    }
}
