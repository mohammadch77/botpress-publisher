<?php

defined('ABSPATH') || exit;

class BotPress_Deactivator {
    public static function deactivate(): void {
        wp_clear_scheduled_hook('botpress_process_queue');
    }
}
