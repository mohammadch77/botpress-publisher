<?php

defined('ABSPATH') || exit;

class BotPress_Schedule_Command extends BotPress_Base_Command {
    public function handle(array $context): array {
        $post_id = (int) ($context['args'][0] ?? 0);

        if (!$post_id) {
            return $this->reply(
                $context,
                "📅 *زمان‌بندی انتشار*\n\n" .
                "استفاده: `/schedule [شناسه مقاله]`\n\n" .
                "مثال: `/schedule 42`\n\n" .
                "یا از /posts لیست مقالات را ببینید."
            );
        }

        // پشتیبانی از /schedule {id} {YYYY-MM-DD HH:MM} برای زمان دلخواه
        if (isset($context['args'][1], $context['args'][2])) {
            $custom = $context['args'][1] . ' ' . $context['args'][2];
            $timestamp = strtotime($custom);
            if ($timestamp === false) {
                return $this->reply($context, "❌ فرمت زمان نامعتبر است. مثال: `2024-12-25 09:00`");
            }
            return $this->do_schedule($context, $post_id, date('Y-m-d H:i:s', $timestamp));
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return $this->reply($context, "❌ مقاله‌ای با شناسه `{$post_id}` یافت نشد.");
        }

        return $this->show_time_picker($context, $post);
    }

    public function handle_callback(array $context): array {
        [$post_id, $time_option] = array_pad(explode(':', $context['value'], 2), 2, '');
        $post_id = (int) $post_id;

        if ($time_option === '') {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'post') {
                return $this->reply($context, '❌ مقاله یافت نشد.');
            }
            return $this->show_time_picker($context, $post);
        }

        if ($time_option === 'custom') {
            return $this->ask_custom_time($context, $post_id);
        }

        $scheduled_at = BotPress_Queue_Manager::preset_time($time_option);
        if (!$scheduled_at) {
            return $this->reply($context, '❌ گزینه زمانی نامعتبر است.');
        }

        return $this->do_schedule($context, $post_id, $scheduled_at);
    }

    private function show_time_picker(array $context, WP_Post $post): array {
        $post_id = $post->ID;
        $title = $this->md($post->post_title);

        $buttons = [];
        foreach (BotPress_Queue_Manager::presets() as $key => $label) {
            $buttons[] = ['text' => $label, 'callback_data' => "schedule_post:{$post_id}:{$key}"];
        }
        $buttons[] = ['text' => '✏️ زمان دلخواه', 'callback_data' => "schedule_post:{$post_id}:custom"];
        $keyboard = array_chunk($buttons, 2);

        return $this->reply(
            $context,
            "📅 *زمان‌بندی انتشار*\n\n" .
            "📄 *{$title}*\n\n" .
            "یک زمان برای انتشار انتخاب کنید:",
            $this->inline_keyboard($keyboard)
        );
    }

    private function ask_custom_time(array $context, int $post_id): array {
        return $this->reply(
            $context,
            "✏️ *زمان دلخواه*\n\n" .
            "تاریخ و ساعت را به این فرمت وارد کنید:\n" .
            "`YYYY-MM-DD HH:MM`\n\n" .
            "مثال: `2024-12-25 09:00`\n\n" .
            "سپس دستور زیر را ارسال کنید:\n" .
            "`/schedule {$post_id} YYYY-MM-DD HH:MM`"
        );
    }

    private function do_schedule(array $context, int $post_id, string $scheduled_at): array {
        $post = get_post($post_id);
        if (!$post) {
            return $this->reply($context, '❌ مقاله یافت نشد.');
        }

        if (!BotPress_Queue_Manager::is_future($scheduled_at)) {
            return $this->reply($context, '❌ زمان انتخاب‌شده گذشته است. یک زمان در آینده وارد کنید.');
        }

        $queue_manager = new BotPress_Queue_Manager();
        $queue_id = $queue_manager->add($post_id, $scheduled_at, 'both');

        if (!$queue_id) {
            return $this->reply($context, '❌ خطا در زمان‌بندی. لطفاً دوباره تلاش کنید.');
        }

        $display_time = mysql2date('Y/m/d H:i', $scheduled_at);

        return $this->reply(
            $context,
            "✅ *زمان‌بندی ثبت شد!*\n\n" .
            "📄 *" . $this->md($post->post_title) . "*\n" .
            "📅 زمان انتشار: *{$display_time}*\n\n" .
            "برای مشاهده صف انتشار: /pending\n" .
            "برای لغو: /cancel {$queue_id}"
        );
    }
}
