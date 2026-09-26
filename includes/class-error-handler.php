<?php

defined('ABSPATH') || exit;

class BotPress_Error_Handler {
    public static function log(string $context, string $message, array $data = []): void {
        $redacted = self::redact($data);
        $suffix = !empty($redacted) ? ' ' . wp_json_encode($redacted) : '';
        error_log("BotPress [{$context}]: {$message}{$suffix}");
    }

    public static function log_to_db(
        int $post_id,
        ?int $channel_id,
        string $action,
        string $status,
        string $message,
        ?string $platform = null
    ): void {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'botpress_logs', [
            'post_id'    => $post_id ?: null,
            'channel_id' => $channel_id,
            'action'     => sanitize_text_field($action),
            'platform'   => $platform,
            'status'     => sanitize_text_field($status),
            'message'    => sanitize_text_field($message),
            'created_at' => current_time('mysql'),
        ]);
    }

    private static function redact(array $data): array {
        $sensitive = ['token', 'secret', 'password', 'api_key'];

        $result = [];
        foreach ($data as $key => $value) {
            $is_sensitive = false;
            foreach ($sensitive as $needle) {
                if (stripos((string) $key, $needle) !== false) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ($is_sensitive) {
                $result[$key] = '****';
            } elseif (is_array($value)) {
                $result[$key] = self::redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
