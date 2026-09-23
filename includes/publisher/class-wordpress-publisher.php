<?php

defined('ABSPATH') || exit;

/**
 * Publishes a post to WordPress itself. Implementation lands in Phase 5.
 */
class BotPress_WordPress_Publisher {
    public function publish(int $post_id): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }
}
