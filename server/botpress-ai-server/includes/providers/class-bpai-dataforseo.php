<?php

defined('ABSPATH') || exit;

/**
 * DataForSEO v3 client. An optional proxy base URL (e.g. a Cloudflare Worker) can replace
 * https://api.dataforseo.com when the host's IP range is blocked by the provider.
 */
class BPAI_DataForSEO {
    private const API_BASE = 'https://api.dataforseo.com';

    private string $login;
    private string $password;
    private string $base;

    public function __construct() {
        $this->login    = (string) BPAI_Settings::get('dfs_login', '');
        $this->password = BPAI_Settings::dfs_password();
        $proxy          = trim((string) BPAI_Settings::get('dfs_proxy_url', ''));
        $this->base     = rtrim($proxy !== '' ? $proxy : self::API_BASE, '/');
    }

    public function is_configured(): bool {
        return $this->login !== '' && $this->password !== '';
    }

    /** @return array|WP_Error */
    public function request(string $method, string $path, ?array $payload = null, int $timeout = 60) {
        if (!$this->is_configured()) {
            return new WP_Error('bpai_dfs_not_configured', 'نام کاربری یا رمز API سرویس DataForSEO تنظیم نشده است.');
        }
        $args = [
            'method'  => $method,
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password),
                'Content-Type'  => 'application/json',
            ],
        ];
        if ($payload !== null) {
            $args['body'] = wp_json_encode($payload);
        }
        $response = wp_remote_request($this->base . '/v3/' . ltrim($path, '/'), $args);
        if (is_wp_error($response)) {
            return new WP_Error('bpai_dfs_http', 'اتصال به DataForSEO برقرار نشد: ' . $response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code === 403 || $code === 451) {
            return new WP_Error('bpai_dfs_blocked', "DataForSEO درخواست را رد کرد (کد {$code}). احتمالاً IP سرور شما مسدود است؛ یک آدرس پروکسی تنظیم کنید.");
        }
        if (!is_array($data) || (int) ($data['status_code'] ?? 0) !== 20000) {
            $message = is_array($data) ? (string) ($data['status_message'] ?? '') : '';
            return new WP_Error('bpai_dfs_error', trim("خطای DataForSEO (کد {$code}): {$message}"));
        }
        return $data;
    }

    /** @return array{login: string, balance: float}|WP_Error */
    public function account() {
        $data = $this->request('GET', 'appendix/user_data', null, 20);
        if (is_wp_error($data)) {
            return $data;
        }
        $result = $data['tasks'][0]['result'][0] ?? [];
        return [
            'login'   => (string) ($result['login'] ?? $this->login),
            'balance' => (float) ($result['money']['balance'] ?? 0),
        ];
    }
}
