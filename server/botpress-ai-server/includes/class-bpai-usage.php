<?php

defined('ABSPATH') || exit;

/** Records every provider call with its real cost, so per-article margins can be measured. */
class BPAI_Usage {
    public static function cost(string $model, int $input_tokens, int $output_tokens): float {
        [$in, $out] = BPAI_Settings::price_for($model);
        return round(($input_tokens * $in + $output_tokens * $out) / 1000000, 2);
    }

    public static function record(array $row): void {
        global $wpdb;
        $row = array_replace([
            'license_id'    => null,
            'job_ref'       => '',
            'stage'         => '',
            'provider'      => 'llm',
            'model'         => '',
            'input_tokens'  => 0,
            'output_tokens' => 0,
            'cost_toman'    => null,
            'duration_ms'   => 0,
            'success'       => 1,
            'error'         => null,
        ], $row);
        if ($row['cost_toman'] === null) {
            $row['cost_toman'] = self::cost($row['model'], (int) $row['input_tokens'], (int) $row['output_tokens']);
        }
        $row['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'bpai_usage', $row);
    }

    public static function summary(int $days = 30): array {
        global $wpdb;
        $since = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
        return $wpdb->get_results($wpdb->prepare(
            "SELECT stage, model, COUNT(*) AS calls, SUM(input_tokens) AS input_tokens,
                    SUM(output_tokens) AS output_tokens, SUM(cost_toman) AS cost_toman, SUM(1 - success) AS failures
             FROM {$wpdb->prefix}bpai_usage WHERE created_at >= %s GROUP BY stage, model ORDER BY cost_toman DESC",
            $since
        ), ARRAY_A) ?: [];
    }

    public static function recent(int $limit = 30): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bpai_usage ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A) ?: [];
    }
}
