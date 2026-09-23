<?php

defined('ABSPATH') || exit;

/**
 * Routes incoming bot commands to their handlers. Implementation lands in Phase 3.
 */
class BotPress_Command_Router {
    private array $commands = [];

    public function register(string $command, callable $handler): void {
        $this->commands[$command] = $handler;
    }

    public function dispatch(string $command, array $context): array {
        return ['success' => false, 'error' => 'not_implemented'];
    }
}
