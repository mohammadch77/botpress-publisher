<?php

defined('ABSPATH') || exit;

class BPAI_Crypto {
    private static function key(): string {
        return hash('sha256', AUTH_KEY . 'bpai_salt_v1', true);
    }

    public static function encrypt(string $value): string {
        if ($value === '') {
            return '';
        }
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', self::key(), 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $value): string {
        if ($value === '') {
            return '';
        }
        $decoded = base64_decode($value);
        $decrypted = openssl_decrypt(substr($decoded, 16), 'AES-256-CBC', self::key(), 0, substr($decoded, 0, 16));
        return $decrypted === false ? '' : $decrypted;
    }

    public static function mask(string $secret): string {
        return strlen($secret) < 8 ? '****' : '****' . substr($secret, -4);
    }

    /** License keys are stored only as hashes; the plain key is shown once at creation. */
    public static function hash_license(string $key): string {
        return hash_hmac('sha256', strtoupper(trim($key)), AUTH_KEY . 'bpai_license');
    }

    public static function generate_license(): string {
        $raw = strtoupper(bin2hex(random_bytes(10)));
        return 'BPAI-' . implode('-', str_split($raw, 5));
    }
}
