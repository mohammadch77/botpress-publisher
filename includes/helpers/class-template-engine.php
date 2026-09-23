<?php

defined('ABSPATH') || exit;

class BotPress_Template_Engine {
    public static function render(string $template, array $variables): string {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }
        return strtr($template, $replacements);
    }

    public static function available_variables(): array {
        return ['title', 'excerpt', 'url', 'date', 'author'];
    }
}
