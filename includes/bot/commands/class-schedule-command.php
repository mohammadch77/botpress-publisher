<?php

defined('ABSPATH') || exit;

class BotPress_Schedule_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);

        if (!$post_id) {
            return $this->reply(
                $context,
                "📅 <b>زمان‌بندی انتشار</b>\n\n" .
                "استفاده: <code>/schedule [شناسه مقاله]</code>\n\n" .
                "مثال: <code>/schedule 42</code>\n\n" .
                "یا از /posts لیست مقالات را ببینید."
            );
        }

        // پشتیبانی از /schedule {id} {YYYY-MM-DD HH:MM} برای زمان دلخواه
        if (isset($context['args'][1], $context['args'][2])) {
            $custom = $context['args'][1] . ' ' . $context['args'][2];
            $timestamp = strtotime($custom);
            if ($timestamp === false) {
                return $this->reply($context, "❌ فرمت زمان نامعتبر است. مثال: <code>2024-12-25 09:00</code>");
            }
            return $this->do_schedule($context, $post_id, date('Y-m-d H:i:s', $timestamp));
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return $this->reply($context, "❌ مقاله‌ای با شناسه <code>{$post_id}</code> یافت نشد.");
        }

        return $this->show_time_picker($context, $post);
    }

    public function handle_callback(array $context): array {
        [$post_id, $time_option] = array_pad(explode(':', $context['value'], 2), 2, '');
        $post_id = (int) $post_id;

        if ($time_option === 'custom') {
            return $this->ask_custom_time($context, $post_id);
        }

        $scheduled_at = $this->resolve_time($time_option);
        if (!$scheduled_at) {
            return $this->reply($context, '❌ گزینه زمانی نامعتبر است.');
        }

        return $this->do_schedule($context, $post_id, $scheduled_at);
    }

    private function show_time_picker(array $context, WP_Post $post): array {
        $post_id = $post->ID;
        $title = esc_html($post->post_title);

        $keyboard = [
            [
                ['text' => '⚡ ۱ ساعت دیگر', 'callback_data' => "schedule_post:{$post_id}:+1hour"],
                ['text' => '🕒 ۳ ساعت دیگر', 'callback_data' => "schedule_post:{$post_id}:+3hours"],
            ],
            [
                ['text' => '🌅 فردا ۸ صبح', 'callback_data' => "schedule_post:{$post_id}:tomorrow_8"],
                ['text' => '☀️ فردا ۱۲ ظهر', 'callback_data' => "schedule_post:{$post_id}:tomorrow_12"],
            ],
            [
                ['text' => '📅 پس‌فردا ۸ صبح', 'callback_data' => "schedule_post:{$post_id}:day_after_8"],
                ['text' => '✏️ زمان دلخواه', 'callback_data' => "schedule_post:{$post_id}:custom"],
            ],
        ];

        return $this->reply(
            $context,
            "📅 <b>زمان‌بندی انتشار</b>\n\n" .
            "📄 <b>{$title}</b>\n\n" .
            "یک زمان برای انتشار انتخاب کنید:",
            $this->inline_keyboard($keyboard)
        );
    }

    private function ask_custom_time(array $context, int $post_id): array {
        return $this->reply(
            $context,
            "✏️ <b>زمان دلخواه</b>\n\n" .
            "تاریخ و ساعت را به این فرمت وارد کنید:\n" .
            "<code>YYYY-MM-DD HH:MM</code>\n\n" .
            "مثال: <code>2024-12-25 09:00</code>\n\n" .
            "سپس دستور زیر را ارسال کنید:\n" .
            "<code>/schedule {$post_id} YYYY-MM-DD HH:MM</code>"
        );
    }

    private function resolve_time(string $option): ?string {
        $now = current_time('timestamp');

        return match ($option) {
            '+1hour' => date('Y-m-d H:i:s', $now + HOUR_IN_SECONDS),
            '+3hours' => date('Y-m-d H:i:s', $now + 3 * HOUR_IN_SECONDS),
            'tomorrow_8' => date('Y-m-d 08:00:00', $now + DAY_IN_SECONDS),
            'tomorrow_12' => date('Y-m-d 12:00:00', $now + DAY_IN_SECONDS),
            'day_after_8' => date('Y-m-d 08:00:00', $now + 2 * DAY_IN_SECONDS),
            default => null,
        };
    }

    private function do_schedule(array $context, int $post_id, string $scheduled_at): array {
        $post = get_post($post_id);
        if (!$post) {
            return $this->reply($context, '❌ مقاله یافت نشد.');
        }

        $queue_manager = new BotPress_Queue_Manager();
        $queue_id = $queue_manager->add($post_id, $scheduled_at, 'both');

        if (!$queue_id) {
            return $this->reply($context, '❌ خطا در زمان‌بندی. لطفاً دوباره تلاش کنید.');
        }

        $display_time = wp_date('Y/m/d H:i', strtotime($scheduled_at));

        return $this->reply(
            $context,
            "✅ <b>زمان‌بندی ثبت شد!</b>\n\n" .
            "📄 <b>" . esc_html($post->post_title) . "</b>\n" .
            "📅 زمان انتشار: <b>{$display_time}</b>\n\n" .
            "برای مشاهده صف انتشار: /pending\n" .
            "برای لغو: /cancel {$queue_id}"
        );
    }
}
