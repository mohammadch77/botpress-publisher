<?php

defined('ABSPATH') || exit;

class BotPress_Posts_Command extends BotPress_Base_Command {
    private const FILTER_MAP = [
        'draft'     => ['draft'],
        'published' => ['publish'],
        'scheduled' => ['future'],
    ];
    private const PER_PAGE = 10;

    public function handle(array $context): array {
        $filter = strtolower($context['args'][0] ?? '');
        $offset = (int) ($context['args'][1] ?? 0);
        return $this->send_list($context, isset(self::FILTER_MAP[$filter]) ? $filter : '', $offset);
    }

    public function handle_callback(array $context): array {
        [$filter, $offset] = array_pad(explode('|', (string) $context['value']), 2, '0');
        return $this->send_list($context, isset(self::FILTER_MAP[$filter]) ? $filter : '', max(0, (int) $offset));
    }

    private function send_list(array $context, string $filter, int $offset): array {
        $statuses = self::FILTER_MAP[$filter] ?? ['draft', 'publish', 'future'];

        $query = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => $statuses,
            'posts_per_page' => self::PER_PAGE,
            'offset'         => $offset,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => false,
        ]);
        $total = (int) $query->found_posts;
        $posts = $query->posts;

        if (empty($posts)) {
            return $this->respond($context, '📋 مقاله‌ای یافت نشد.');
        }

        $text = "📋 *مقالات* ({$total})\n\n";
        $buttons = [];
        $i = $offset + 1;
        foreach ($posts as $post) {
            $text .= "{$i}. " . $this->md($post->post_title) . ' — _' . $this->status_label($post->post_status) . "_\n";
            $buttons[] = [[
                'text'          => "🔍 {$i}. جزئیات",
                'callback_data' => 'post_detail:' . $post->ID,
            ]];
            $i++;
        }
        $text .= "\nبرای جزئیات روی دکمه‌ها کلیک کنید:";

        $nav = [];
        if ($offset > 0) {
            $nav[] = ['text' => '⏮ قبلی', 'callback_data' => 'posts_list:' . $filter . '|' . max(0, $offset - self::PER_PAGE)];
        }
        if ($offset + self::PER_PAGE < $total) {
            $nav[] = ['text' => 'بعدی ⏭', 'callback_data' => 'posts_list:' . $filter . '|' . ($offset + self::PER_PAGE)];
        }
        if ($nav) {
            $buttons[] = $nav;
        }

        return $this->respond($context, $text, $this->inline_keyboard($buttons));
    }
}
