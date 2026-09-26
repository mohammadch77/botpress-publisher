<?php

defined('ABSPATH') || exit;

class BotPress_Search_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $query = trim(implode(' ', $context['args'] ?? []));

        if ($query === '') {
            return $this->reply($context, '🔎 لطفاً عبارت جستجو را وارد کنید. مثال: /search عنوان مقاله');
        }

        return $this->send_results($context['driver'], $context['chat_id'], $query);
    }

    private function send_results(BotPress_Bot_Driver_Interface $driver, string $chat_id, string $query): array {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => ['draft', 'publish', 'future'],
            's'              => $query,
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (empty($posts)) {
            $text = '🔎 نتیجه‌ای برای «' . esc_html($query) . '» یافت نشد.';
            return $driver->send_message($chat_id, $text);
        }

        $text = '🔎 <b>نتایج جستجو برای «' . esc_html($query) . '»</b> (' . count($posts) . ")\n\n";
        $buttons = [];
        $i = 1;
        foreach ($posts as $post) {
            $status_map = [
                'publish' => 'منتشر شده',
                'draft'   => 'پیش‌نویس',
                'pending' => 'در انتظار بررسی',
                'future'  => 'زمان‌بندی شده',
                'private' => 'خصوصی',
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

        $options = $this->inline_keyboard($buttons);

        return $driver->send_message($chat_id, $text, $options);
    }
}
