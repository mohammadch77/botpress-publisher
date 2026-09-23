<?php

defined('ABSPATH') || exit;

class BotPress_Driver_Factory {
    public static function make(string $platform): ?BotPress_Bot_Driver_Interface {
        $encrypted = get_option("botpress_bot_token_{$platform}_enc", '');
        if (empty($encrypted)) {
            return null;
        }

        $token = BotPress_Encryption::decrypt($encrypted);
        if (empty($token)) {
            return null;
        }

        return self::create($platform, $token);
    }

    public static function make_from_channel(object $channel): ?BotPress_Bot_Driver_Interface {
        $token = BotPress_Encryption::decrypt($channel->bot_token_enc);
        if (empty($token)) {
            return null;
        }

        return self::create($channel->platform, $token);
    }

    private static function create(string $platform, string $token): ?BotPress_Bot_Driver_Interface {
        switch ($platform) {
            case 'telegram':
                return new BotPress_Telegram_Driver($token);
            case 'bale':
                return new BotPress_Bale_Driver($token);
            default:
                return null;
        }
    }
}
