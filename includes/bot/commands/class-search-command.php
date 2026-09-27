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
            $text = '🔎 نتیجه‌ای برای «' . $this->md($query) . '» یافت نشد.';
            return $driver->send_message($chat_id, $text);
        }

        $text = '🔎 *نتایج جستجو برای «' . $this->md($query) . '»* (' . count($posts) . ")\n\n";
        $buttons = [];
        $i = 1;
        foreach ($posts as $post) {
            $text .= "{$i}. " . $this->md($post->post_title) . ' — _' . $this->status_label($post->post_status) . "_\n";
            $buttons[] = [[
                'text'          => "🔍 {$i}. جزئیات",
                'callback_data' => 'post_detail:' . $post->ID,
            ]];
            $i++;
        }
        $text .= "\nبرای جزئیات روی دکمه‌ها کلیک کنید:";

        $options = $this->inline_keyboard($buttons);

        return $driver->send_message($chat_id, $text, $options);
    }
}
