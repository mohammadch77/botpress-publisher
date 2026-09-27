<?php

defined('ABSPATH') || exit;

/**
 * Central configuration. Secrets are stored encrypted; everything else is a plain option array.
 */
class BPAI_Settings {
    private const OPTION = 'bpai_settings';

    /** Pipeline stages and the model each one uses by default. */
    public static function stages(): array {
        return [
            'research' => ['label' => 'تحقیق رقبا، بریف، کلیدواژه و دسته‌بندی', 'default' => 'Gemini-3-Flash-Preview'],
            'writer'   => ['label' => 'نگارش مقاله (استاندارد)', 'default' => 'Claude-Sonnet-4.6'],
            'writer_pro' => ['label' => 'نگارش مقاله (حرفه‌ای)', 'default' => 'Claude-Fable-5-1'],
            'editor'   => ['label' => 'ویراستاری و ممیزی سئو', 'default' => 'Gemini-3.1-Pro-Preview'],
            'image'    => ['label' => 'ساخت تصویر (استاندارد)', 'default' => 'Gemini-3.1-Flash-Image-Preview'],
            'image_pro' => ['label' => 'ساخت تصویر (حرفه‌ای)', 'default' => 'Gemini-3-Pro-Image-Preview'],
            'embedding' => ['label' => 'لینک‌سازی داخلی (Embedding)', 'default' => 'Gemini-embedding-001'],
        ];
    }

    /** Toman per 1M tokens [input, output], as listed in the ArvanCloud model marketplace. */
    public static function default_prices(): array {
        return [
            'Gemini-3-Flash-Preview'         => [130000, 780000],
            'GPT-5.6-Luna'                   => [240000, 1440000],
            'Gemini-3.1-Pro-Preview'         => [520000, 3120000],
            'GPT-5.6-Terra'                  => [600000, 3600000],
            'Claude-Sonnet-4.6'              => [780000, 3900000],
            'Claude-Opus-4.7'                => [1300000, 6500000],
            'Claude-Fable-5-1'               => [2600000, 13000000],
            'Gemini-3.1-Flash-Image-Preview' => [130000, 15600000],
            'Gemini-3-Pro-Image-Preview'     => [520000, 3120000],
            'GPT-Image-1'                    => [1050000, 1050000],
            'Gemini-embedding-001'           => [39000, 0],
        ];
    }

    public static function defaults(): array {
        $models = [];
        foreach (self::stages() as $key => $stage) {
            $models[$key] = $stage['default'];
        }
        return [
            'llm_base_url'     => '',
            'llm_auth_scheme'  => 'apikey',
            'llm_api_key_enc'  => '',
            'llm_timeout'      => 120,
            'models'           => $models,
            'prices'           => self::default_prices(),
            'dfs_login'        => '',
            'dfs_password_enc' => '',
            'dfs_proxy_url'    => '',
            'dfs_location'     => 2364,
            'dfs_language'     => 'fa',
            'credit_price'     => 690000,
            'pro_credit_cost'  => 2,
        ];
    }

    public static function all(): array {
        $stored = get_option(self::OPTION, []);
        $merged = array_replace(self::defaults(), is_array($stored) ? $stored : []);
        $merged['models'] = array_replace(self::defaults()['models'], (array) ($merged['models'] ?? []));
        $merged['prices'] = (array) ($merged['prices'] ?? []);
        return $merged;
    }

    public static function get(string $key, $fallback = null) {
        $all = self::all();
        return $all[$key] ?? $fallback;
    }

    public static function update(array $values): void {
        update_option(self::OPTION, array_replace(self::all(), $values), false);
    }

    public static function llm_api_key(): string {
        return BPAI_Crypto::decrypt((string) self::get('llm_api_key_enc', ''));
    }

    public static function dfs_password(): string {
        return BPAI_Crypto::decrypt((string) self::get('dfs_password_enc', ''));
    }

    public static function model_for(string $stage): string {
        $models = self::get('models', []);
        return (string) ($models[$stage] ?? self::stages()[$stage]['default'] ?? '');
    }

    /** @return array{0: float, 1: float} Toman per 1M tokens. */
    public static function price_for(string $model): array {
        $prices = self::get('prices', []);
        $price = $prices[$model] ?? [0, 0];
        return [(float) ($price[0] ?? 0), (float) ($price[1] ?? 0)];
    }
}
