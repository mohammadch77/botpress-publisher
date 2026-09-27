<?php

defined('ABSPATH') || exit;

/**
 * Message formatting shared by Telegram and Bale (legacy Markdown: *bold* _italic_ `code` [text](url)).
 */
class BotPress_Markdown {
    // Markdown control characters in dynamic text are swapped for look-alikes so user content can never break parsing.
    private const ESCAPE_MAP = [
        '*' => '∗',
        '_' => '‗',
        '`' => 'ˋ',
        '[' => '(',
        ']' => ')',
    ];

    public static function escape(?string $text): string {
        $text = html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return strtr($text, self::ESCAPE_MAP);
    }

    public static function bold(?string $text): string {
        return '*' . self::escape($text) . '*';
    }

    public static function italic(?string $text): string {
        return '_' . self::escape($text) . '_';
    }

    public static function code(?string $text): string {
        return '`' . str_replace('`', 'ˋ', (string) $text) . '`';
    }

    public static function link(?string $text, string $url): string {
        return '[' . self::escape($text) . '](' . self::url($url) . ')';
    }

    public static function url(string $url): string {
        return str_replace([' ', '(', ')'], ['%20', '%28', '%29'], trim($url));
    }

    /** Converts legacy HTML templates/messages (<b>, <i>, <a>, ...) to Markdown. */
    public static function from_html(string $html): string {
        if (!preg_match('/<\/?[a-z][^>]*>/i', $html)) {
            return $html;
        }

        $md = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $md = preg_replace_callback(
            '/<a\s[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is',
            static fn($m) => '[' . wp_strip_all_tags($m[3]) . '](' . self::url(html_entity_decode($m[2])) . ')',
            $md
        );
        $md = preg_replace('/<(b|strong)>(.*?)<\/\1>/is', '*$2*', $md);
        $md = preg_replace('/<(i|em)>(.*?)<\/\1>/is', '_$2_', $md);
        $md = preg_replace('/<(code|pre)>(.*?)<\/\1>/is', '`$2`', $md);
        $md = wp_strip_all_tags($md);

        return html_entity_decode($md, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Plain-text fallback when a platform rejects the Markdown. */
    public static function to_plain(string $markdown): string {
        $plain = preg_replace('/\[([^\]]*)\]\(([^)]*)\)/', '$1: $2', $markdown);
        return str_replace(['*', '_', '`'], '', $plain);
    }
}
