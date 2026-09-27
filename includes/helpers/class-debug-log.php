<?php

defined('ABSPATH') || exit;

/** Ring buffer of recent bot activity (updates, decisions, API errors) shown in the admin for diagnostics. */
class BotPress_Debug_Log {
    private const OPTION = 'botpress_bot_debug_log';
    private const MAX = 40;

    public static function add(string $platform, string $level, string $event, string $detail = ''): void {
        $entries = get_option(self::OPTION, []);
        if (!is_array($entries)) {
            $entries = [];
        }
        array_unshift($entries, [
            'time'     => gmdate('c'),
            'platform' => $platform,
            'level'    => $level,
            'event'    => $event,
            'detail'   => mb_substr($detail, 0, 600),
        ]);
        update_option(self::OPTION, array_slice($entries, 0, self::MAX), false);
    }

    public static function all(string $platform = ''): array {
        $entries = get_option(self::OPTION, []);
        if (!is_array($entries)) {
            return [];
        }
        if ($platform === '') {
            return $entries;
        }
        return array_values(array_filter($entries, static fn($e) => ($e['platform'] ?? '') === $platform));
    }

    public static function clear(): void {
        delete_option(self::OPTION);
    }
}
