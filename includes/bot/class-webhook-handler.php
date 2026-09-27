<?php

defined('ABSPATH') || exit;

class BotPress_Webhook_Handler {
    private BotPress_Command_Router $router;

    public function __construct() {
        $this->router = new BotPress_Command_Router();
    }

    public function handle(string $platform): void {
        http_response_code(200);
        header('Content-Type: application/json');

        $payload = $this->get_payload();
        if (!$payload) {
            BotPress_Debug_Log::add($platform, 'warn', 'بدنه درخواست خالی یا نامعتبر بود');
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        if ($platform === 'telegram') {
            $secret = get_option('botpress_webhook_secret', '');
            $header_secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($secret && $header_secret !== $secret) {
                BotPress_Debug_Log::add($platform, 'warn', 'درخواست با secret نامعتبر رد شد');
                echo wp_json_encode(['ok' => true]);
                exit;
            }
        }

        update_option("botpress_webhook_last_hit_{$platform}", time(), false);
        BotPress_Debug_Log::add($platform, 'info', 'دریافت: ' . $this->describe($payload), $this->payload_excerpt($payload));

        register_shutdown_function(static function () use ($platform) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                BotPress_Debug_Log::add($platform, 'error', 'خطای مرگبار PHP', $error['message'] . ' @ ' . basename($error['file']) . ':' . $error['line']);
            }
        });

        $update_id = $payload['update_id'] ?? null;
        if ($this->is_duplicate_update($platform, $payload)) {
            BotPress_Debug_Log::add($platform, 'warn', "آپدیت تکراری نادیده گرفته شد (update_id={$update_id})");
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        $ignore_reason = $this->ignore_reason($payload);
        if ($ignore_reason !== null) {
            BotPress_Debug_Log::add($platform, 'info', "نادیده گرفته شد: {$ignore_reason}");
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        $from_id = $this->extract_user_id($payload);
        if (!$this->is_authorized($from_id)) {
            BotPress_Debug_Log::add($platform, 'warn', 'کاربر مجاز نیست (from=' . ($from_id ?? 'نامشخص') . ')');
            $this->send_unauthorized_message($platform, $payload);
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        $chat_id = $payload['callback_query']['message']['chat']['id'] ?? $payload['message']['chat']['id'] ?? $from_id;
        BotPress_Notifier::remember_chat($platform, (string) $chat_id);

        if ($from_id !== null && !$this->check_rate_limit($from_id)) {
            BotPress_Debug_Log::add($platform, 'warn', "محدودیت تعداد درخواست برای کاربر {$from_id}");
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        try {
            $this->router->route($platform, $payload);
        } catch (Throwable $e) {
            BotPress_Debug_Log::add($platform, 'error', 'خطا هنگام پردازش', get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        }

        echo wp_json_encode(['ok' => true]);
        exit;
    }

    // The bot is only controlled from private chats; channel/group posts (including its own publications) are echoes, not commands.
    private function ignore_reason(array $payload): ?string {
        $callback = $payload['callback_query'] ?? null;
        $message = $callback['message'] ?? $payload['message'] ?? null;

        if (!$callback && !isset($payload['message'])) {
            return 'پست کانال یا نوع آپدیت غیرقابل پردازش';
        }

        $from = $callback['from'] ?? $payload['message']['from'] ?? null;
        if (!$from || empty($from['id'])) {
            return 'پیام بدون فرستنده (پست کانال)';
        }
        if (!empty($from['is_bot'])) {
            return 'پیام از طرف ربات';
        }

        $chat_type = $message['chat']['type'] ?? 'private';
        if ($chat_type !== 'private') {
            return "پیام در چت از نوع {$chat_type}";
        }

        return null;
    }

    private function describe(array $payload): string {
        if (isset($payload['callback_query'])) {
            return 'دکمه (callback) data=' . ($payload['callback_query']['data'] ?? '—');
        }
        $message = $payload['message'] ?? $payload['edited_message'] ?? $payload['channel_post'] ?? null;
        if ($message) {
            return 'پیام: ' . mb_substr((string) ($message['text'] ?? '[بدون متن]'), 0, 60);
        }
        return 'نوع ناشناخته: ' . implode(',', array_keys($payload));
    }

    private function payload_excerpt(array $payload): string {
        return (string) wp_json_encode($payload);
    }

    private const MAX_PAYLOAD_BYTES = 1048576; // 1MB
    private const RATE_LIMIT_MAX = 30;
    private const RATE_LIMIT_WINDOW = 60;

    private function get_payload(): ?array {
        $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        if ($content_length > self::MAX_PAYLOAD_BYTES) {
            error_log('BotPress webhook rejected: payload exceeds size limit');
            return null;
        }

        $body = file_get_contents('php://input');
        if (empty($body) || strlen($body) > self::MAX_PAYLOAD_BYTES) {
            if (!empty($body)) {
                error_log('BotPress webhook rejected: payload exceeds size limit');
            }
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    public function check_rate_limit(string $user_id): bool {
        $key = 'botpress_rl_' . md5($user_id);
        $count = (int) get_transient($key);

        if ($count >= self::RATE_LIMIT_MAX) {
            return false;
        }

        if ($count === 0) {
            set_transient($key, 1, self::RATE_LIMIT_WINDOW);
        } else {
            set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
        }

        return true;
    }

    // Keyed on the whole update, not update_id alone: platforms may reuse update_id values across distinct updates.
    private function is_duplicate_update(string $platform, array $payload): bool {
        $key = 'botpress_upd_' . $platform . '_' . md5((string) wp_json_encode($payload));
        if (get_transient($key)) {
            return true;
        }
        set_transient($key, 1, HOUR_IN_SECONDS);
        return false;
    }

    private function extract_user_id(array $payload): ?string {
        $id = $payload['message']['from']['id']
            ?? $payload['callback_query']['from']['id']
            ?? $payload['callback_query']['message']['chat']['id']
            ?? null;
        return $id === null ? null : (string) $id;
    }

    private function is_authorized(?string $user_id): bool {
        if (!$user_id) {
            return false;
        }
        $authorized = get_option('botpress_authorized_users', []);
        if (empty($authorized)) {
            return true;
        }
        return in_array($user_id, array_map('strval', $authorized), true);
    }

    private function send_unauthorized_message(string $platform, array $payload): void {
        $chat_id = $payload['message']['chat']['id']
            ?? $payload['callback_query']['message']['chat']['id']
            ?? null;
        if (!$chat_id) {
            return;
        }

        $driver = BotPress_Driver_Factory::make($platform);
        $driver?->send_message((string) $chat_id, '⛔ شما مجاز به استفاده از این ربات نیستید.');
    }
}
