<?php

defined('ABSPATH') || exit;

class BotPress_Command_Router {
    public function route(string $platform, array $payload): void {
        $driver = BotPress_Driver_Factory::make($platform);
        if (!$driver) {
            return;
        }

        if (isset($payload['callback_query'])) {
            $this->handle_callback($driver, $payload['callback_query']);
            return;
        }

        $message = $payload['message'] ?? $payload['channel_post'] ?? null;
        if (!$message) {
            return;
        }

        $text = trim($message['text'] ?? '');
        $chat_id = (string) $message['chat']['id'];
        $from = $message['from'] ?? [];

        $command = $this->parse_command($text);
        $args = $this->parse_args($text);

        $this->dispatch($driver, $command, $args, $chat_id, $from, $message);
    }

    private function parse_command(string $text): string {
        if (empty($text) || $text[0] !== '/') {
            return 'unknown';
        }
        $parts = explode(' ', $text);
        $cmd = strtolower($parts[0]);
        $cmd = explode('@', $cmd)[0];
        return $cmd;
    }

    private function parse_args(string $text): array {
        $parts = explode(' ', trim($text));
        array_shift($parts);
        return array_values(array_filter($parts, static fn($p) => $p !== ''));
    }

    private function dispatch(
        BotPress_Bot_Driver_Interface $driver,
        string $command,
        array $args,
        string $chat_id,
        array $from,
        array $message
    ): void {
        $context = compact('driver', 'args', 'chat_id', 'from', 'message');

        switch ($command) {
            case '/start':
                (new BotPress_Start_Command())->handle($context);
                break;
            case '/help':
                (new BotPress_Help_Command())->handle($context);
                break;
            case '/posts':
                (new BotPress_Posts_Command())->handle($context);
                break;
            case '/search':
                (new BotPress_Search_Command())->handle($context);
                break;
            case '/post':
                (new BotPress_Post_Detail_Command())->handle($context);
                break;
            case '/schedule':
                (new BotPress_Schedule_Command())->handle($context);
                break;
            case '/publish':
                (new BotPress_Publish_Command())->handle($context);
                break;
            case '/channels':
                (new BotPress_Channels_Command())->handle($context);
                break;
            case '/status':
                (new BotPress_Status_Command())->handle($context);
                break;
            case '/pending':
                (new BotPress_Pending_Command())->handle($context);
                break;
            case '/cancel':
                (new BotPress_Cancel_Command())->handle($context);
                break;
            default:
                $driver->send_message($chat_id, "❓ دستور نامعتبر.\n\nبرای راهنمایی /help را ارسال کنید.");
        }
    }

    private function handle_callback(BotPress_Bot_Driver_Interface $driver, array $callback): void {
        $callback_id = $callback['id'];
        $data = $callback['data'] ?? '';
        $chat_id = (string) $callback['message']['chat']['id'];
        $message_id = (int) $callback['message']['message_id'];

        $driver->answer_callback($callback_id);

        [$action, $value] = array_pad(explode(':', $data, 2), 2, '');

        $context = [
            'driver'      => $driver,
            'chat_id'     => $chat_id,
            'message_id'  => $message_id,
            'callback_id' => $callback_id,
            'action'      => $action,
            'value'       => $value,
        ];

        switch ($action) {
            case 'post_detail':
                (new BotPress_Post_Detail_Command())->handle_callback($context);
                break;
            case 'posts_list':
                (new BotPress_Posts_Command())->handle_callback($context);
                break;
            case 'publish_now':
                (new BotPress_Publish_Command())->handle_callback($context);
                break;
            case 'schedule_post':
                (new BotPress_Schedule_Command())->handle_callback($context);
                break;
            case 'cancel_queue':
                (new BotPress_Cancel_Command())->handle_callback($context);
                break;
        }
    }
}
