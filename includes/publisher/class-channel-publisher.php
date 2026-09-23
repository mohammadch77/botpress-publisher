<?php

defined('ABSPATH') || exit;

/**
 * Publishes a post to a bot channel. Implementation lands in Phase 5.
 */
class BotPress_Channel_Publisher {
    public function publish(int $post_id, int $channel_id): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }
}
