<?php

defined('ABSPATH') || exit;

class BotPress_Encryption {
    private static function get_key(): string {
        return hash('sha256', AUTH_KEY . 'botpress_salt_v1', true);
    }

    public static function encrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', self::get_key(), 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', self::get_key(), 0, $iv);
        return $decrypted === false ? '' : $decrypted;
    }

    public static function mask(string $token): string {
        if (strlen($token) < 8) {
            return '****';
        }
        return '****' . substr($token, -4);
    }
}
