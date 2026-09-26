<?php

defined('ABSPATH') || exit;

class BotPress_Posts_Command extends BotPress_Base_Command {
    private const FILTER_MAP = [
        'draft'     => ['draft'],
        'published' => ['publish'],
        'scheduled' => ['future'],
    ];

    public function handle(array $context): array {
        $filter = strtolower($context['args'][0] ?? '');
        $statuses = self::FILTER_MAP[$filter] ?? ['draft', 'publish', 'future'];
        $offset = (int) ($context['args'][1] ?? 0);
        return $this->send_list($context['driver'], $context['chat_id'], $statuses, $offset);
    }

    public function handle_callback(array $context): array {
        [$filter, $offset] = array_pad(explode('|', (string) $context['value']), 2, '0');
        $statuses = self::FILTER_MAP[$filter] ?? ['draft', 'publish', 'future'];
        return $this->send_list($context['driver'], $context['chat_id'], $statuses, (int) $offset, (int) $context['message_id']);
    }

    private function send_list(
        BotPress_Bot_Driver_Interface $driver,
        string $chat_id,
        array $statuses,
        int $offset = 0,
        ?int $message_id = null
    ): array {
        $per_page = 10;
        $total = count(get_posts([
            'post_type'      => 'post',
            'post_status'    => $statuses,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]));

        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => $statuses,
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (empty($posts)) {
            $text = '📋 مقاله‌ای یافت نشد.';
            return $message_id
                ? $driver->edit_message($chat_id, $message_id, $text)
                : $driver->send_message($chat_id, $text);
        }

        $text = "📋 <b>مقالات</b> ({$total})\n\n";
        $buttons = [];
        $i = $offset + 1;
        foreach ($posts as $post) {
            $status_map = [
                'publish' => 'منتشر شده',
                'draft'   => 'پیش‌نویس',
                'pending' => 'در انتظار بررسی',
                'future'  => 'زمان‌بندی شده',
                'private' => 'خصوصی',
                'trash'   => 'سطل زباله',
            ];
            $status = $status_map[$post->post_status] ?? $post->post_status;
            $text .= "{$i}. " . esc_html($post->post_title) . " — <i>{$status}</i>\n";
            $buttons[] = [[
                'text'          => "{$i}. جزئیات 🔍",
                'callback_data' => 'post_detail:' . $post->ID,
            ]];
            $i++;
        }
        $text .= "\nبرای جزئیات روی دکمه‌ها کلیک کنید:";

        $filter_key = array_search($statuses, self::FILTER_MAP, true) ?: '';
        if ($offset + $per_page < $total) {
            $buttons[] = [[
                'text'          => 'بیشتر ⏭',
                'callback_data' => 'posts_list:' . $filter_key . '|' . ($offset + $per_page),
            ]];
        }

        $options = $this->inline_keyboard($buttons);

        return $message_id
            ? $driver->edit_message($chat_id, $message_id, $text, $options)
            : $driver->send_message($chat_id, $text, $options);
    }
}
