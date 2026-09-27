<?php

defined('ABSPATH') || exit;

class BPAI_Licenses {
    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'bpai_licenses';
    }

    /** @return array{id: int, key: string} The plain key is returned only here. */
    public static function create(string $customer_name, string $contact, int $initial_credits = 0, string $note = ''): array {
        global $wpdb;
        $key = BPAI_Crypto::generate_license();
        $wpdb->insert(self::table(), [
            'key_hash'         => BPAI_Crypto::hash_license($key),
            'key_hint'         => substr($key, -5),
            'customer_name'    => $customer_name,
            'customer_contact' => $contact,
            'credits'          => 0,
            'note'             => $note,
            'created_at'       => current_time('mysql'),
        ]);
        $id = (int) $wpdb->insert_id;
        if ($initial_credits !== 0) {
            self::adjust_credits($id, $initial_credits, 'manual', 'اعتبار اولیه');
        }
        return ['id' => $id, 'key' => $key];
    }

    public static function find(int $id): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id), ARRAY_A);
        return $row ?: null;
    }

    public static function find_by_key(string $key): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE key_hash = %s', BPAI_Crypto::hash_license($key)),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function all(): array {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM ' . self::table() . ' ORDER BY id DESC', ARRAY_A) ?: [];
    }

    public static function set_status(int $id, string $status): void {
        global $wpdb;
        if (in_array($status, ['active', 'suspended'], true)) {
            $wpdb->update(self::table(), ['status' => $status], ['id' => $id]);
        }
    }

    public static function reset_site(int $id): void {
        global $wpdb;
        $wpdb->update(self::table(), ['site_url' => ''], ['id' => $id]);
    }

    public static function normalize_site(string $url): string {
        $host = wp_parse_url(trim($url), PHP_URL_HOST);
        return $host ? strtolower(preg_replace('/^www\./i', '', $host)) : '';
    }

    /**
     * Validates a client request. A license is bound to the first site that uses it;
     * a different site gets rejected until the admin resets the binding.
     *
     * @return array|WP_Error License row on success.
     */
    public static function authenticate(string $key, string $site_url) {
        global $wpdb;
        $license = $key !== '' ? self::find_by_key($key) : null;
        if (!$license) {
            return new WP_Error('bpai_invalid_license', 'کلید لایسنس نامعتبر است.', ['status' => 401]);
        }
        if ($license['status'] !== 'active') {
            return new WP_Error('bpai_suspended', 'این لایسنس غیرفعال شده است. با پشتیبانی تماس بگیرید.', ['status' => 403]);
        }
        $site = self::normalize_site($site_url);
        if ($site === '') {
            return new WP_Error('bpai_no_site', 'آدرس سایت ارسال نشده است.', ['status' => 400]);
        }
        if ($license['site_url'] === '') {
            $wpdb->update(self::table(), ['site_url' => $site], ['id' => $license['id']]);
            $license['site_url'] = $site;
        } elseif ($license['site_url'] !== $site) {
            return new WP_Error(
                'bpai_site_mismatch',
                'این لایسنس برای سایت دیگری فعال شده است. برای انتقال با پشتیبانی تماس بگیرید.',
                ['status' => 403]
            );
        }
        $wpdb->update(self::table(), ['last_seen_at' => current_time('mysql')], ['id' => $license['id']]);
        return $license;
    }

    /** Atomic credit change; refuses to go negative. Returns new balance or WP_Error. */
    public static function adjust_credits(int $id, int $delta, string $reason, string $reference = '') {
        global $wpdb;
        $table = self::table();
        if ($delta < 0) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET credits = credits + %d WHERE id = %d AND credits + %d >= 0",
                $delta, $id, $delta
            ));
            if (!$updated) {
                return new WP_Error('bpai_no_credit', 'اعتبار کافی نیست.', ['status' => 402]);
            }
        } else {
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET credits = credits + %d WHERE id = %d", $delta, $id));
        }
        $balance = (int) $wpdb->get_var($wpdb->prepare("SELECT credits FROM {$table} WHERE id = %d", $id));
        $wpdb->insert($wpdb->prefix . 'bpai_credit_ledger', [
            'license_id'    => $id,
            'delta'         => $delta,
            'balance_after' => $balance,
            'reason'        => $reason,
            'reference'     => $reference,
            'created_at'    => current_time('mysql'),
        ]);
        return $balance;
    }

    public static function ledger(int $id, int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bpai_credit_ledger WHERE license_id = %d ORDER BY id DESC LIMIT %d",
            $id, $limit
        ), ARRAY_A) ?: [];
    }
}
