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
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        if ($platform === 'telegram') {
            $secret = get_option('botpress_webhook_secret', '');
            $header_secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($secret && $header_secret !== $secret) {
                echo wp_json_encode(['ok' => true]);
                exit;
            }
        }

        $from_id = $this->extract_user_id($payload);
        if (!$this->is_authorized($from_id)) {
            $this->send_unauthorized_message($platform, $payload);
            echo wp_json_encode(['ok' => true]);
            exit;
        }

        try {
            $this->router->route($platform, $payload);
        } catch (Throwable $e) {
            error_log('BotPress webhook error: ' . $e->getMessage());
        }

        echo wp_json_encode(['ok' => true]);
        exit;
    }

    private function get_payload(): ?array {
        $body = file_get_contents('php://input');
        if (empty($body)) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private function extract_user_id(array $payload): ?string {
        $id = $payload['message']['from']['id']
            ?? $payload['callback_query']['from']['id']
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
