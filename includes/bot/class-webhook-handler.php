<?php

defined('ABSPATH') || exit;

/**
 * Incoming bot webhook handler. Implementation lands in Phase 3.
 */
class BotPress_Webhook_Handler {
    public function handle(string $platform, array $payload): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }
}
