<?php

defined('ABSPATH') || exit;

/**
 * Normalizes the "channel link / chat id" field a user can paste for
 * Telegram or Bale into the chat_id format the Bot API expects.
 *
 * Accepted inputs (either platform):
 *  - numeric id:            -1001234567890 / 4607123600
 *  - @username / username:  @botpress / botpress
 *  - public link:           https://t.me/botpress / https://ble.ir/botpress
 *  - bale numeric-chat url: https://web.bale.ai/chat?uid=4607123600
 *  - private invite link:   https://t.me/joinchat/XXXX, https://t.me/+XXXX,
 *                           https://ble.ir/join/XXXX
 *    These cannot be resolved to a chat_id via the Bot API (Telegram/Bale
 *    never expose that mapping to bots), so they are kept as-is and the
 *    caller is told resolution could not be confirmed automatically.
 */
class BotPress_Chat_Id_Resolver {

    public static function resolve(string $input, string $platform, string $bot_token = ''): array {
        $input = trim($input);

        if ($input === '') {
            return ['success' => false, 'chat_id' => '', 'message' => 'empty_input'];
        }

        // Plain numeric id (works for both platforms as-is).
        if (preg_match('/^-?\d+$/', $input)) {
            return ['success' => true, 'chat_id' => $input, 'resolved' => false];
        }

        $parsed = self::parse($input);

        if ($parsed['type'] === 'numeric') {
            return ['success' => true, 'chat_id' => $parsed['value'], 'resolved' => false];
        }

        if ($parsed['type'] === 'invite') {
            return [
                'success'  => true,
                'chat_id'  => $input,
                'resolved' => false,
                'message'  => 'private_invite_unresolved',
            ];
        }

        if ($parsed['type'] === 'username') {
            $username = '@' . ltrim($parsed['value'], '@');

            if ($bot_token === '') {
                return ['success' => true, 'chat_id' => $username, 'resolved' => false];
            }

            $driver = $platform === 'bale'
                ? new BotPress_Bale_Driver($bot_token)
                : new BotPress_Telegram_Driver($bot_token);

            $result = $driver->get_chat($username);

            if (!empty($result['ok']) && isset($result['result']['id'])) {
                return [
                    'success'      => true,
                    'chat_id'      => (string) $result['result']['id'],
                    'bot_username' => $result['result']['username'] ?? ltrim($username, '@'),
                    'resolved'     => true,
                ];
            }

            // Couldn't verify via API right now (bot not admin yet, etc.) —
            // keep the username, Test Connection will surface the real error.
            return [
                'success'  => true,
                'chat_id'  => $username,
                'resolved' => false,
                'message'  => $result['description'] ?? 'username_unresolved',
            ];
        }

        return ['success' => false, 'chat_id' => '', 'message' => 'invalid_input'];
    }

    private static function parse(string $input): array {
        if (preg_match('/^@?([A-Za-z][A-Za-z0-9_]{4,31})$/', $input, $m)) {
            return ['type' => 'username', 'value' => $m[1]];
        }

        $url = preg_match('#^https?://#i', $input) ? $input : 'https://' . $input;
        $parts = wp_parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return ['type' => 'invalid'];
        }

        $host = strtolower($parts['host']);
        $path = trim($parts['path'] ?? '', '/');

        // https://web.bale.ai/chat?uid=4607123600
        if (strpos($host, 'bale.ai') !== false && $path === 'chat' && !empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['uid']) && preg_match('/^-?\d+$/', (string) $query['uid'])) {
                return ['type' => 'numeric', 'value' => (string) $query['uid']];
            }
        }

        // Private invite links: t.me/joinchat/XXXX, t.me/+XXXX, ble.ir/join/XXXX
        if (
            strpos($path, 'joinchat/') === 0 ||
            strpos($path, '+') === 0 ||
            strpos($path, 'join/') === 0
        ) {
            return ['type' => 'invite'];
        }

        // Public link: t.me/username, ble.ir/username
        if ($path !== '' && preg_match('/^([A-Za-z][A-Za-z0-9_]{4,31})$/', $path, $m)) {
            return ['type' => 'username', 'value' => $m[1]];
        }

        return ['type' => 'invalid'];
    }
}
