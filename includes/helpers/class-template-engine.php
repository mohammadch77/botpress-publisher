<?php

defined('ABSPATH') || exit;

class BotPress_Template_Engine {
    private string $template;

    public function __construct(string $template = '', string $platform = 'default') {
        $this->template = $template !== '' ? $template : self::get_template($platform);
    }

    public static function default_template(): string {
        return "📌 *{title}*\n\n{excerpt}\n\n🔗 [ادامه مطلب]({url})";
    }

    public static function get_templates(): array {
        $stored = get_option('botpress_templates', null);
        if (is_array($stored)) {
            $templates = array_merge(
                ['default' => self::default_template(), 'telegram' => '', 'bale' => ''],
                $stored
            );
        } else {
            // Migrate legacy single-template option.
            $legacy = (string) get_option('botpress_default_template', '');
            $templates = [
                'default'  => $legacy !== '' ? $legacy : self::default_template(),
                'telegram' => '',
                'bale'     => '',
            ];
        }

        // Templates saved before the switch to Markdown may still contain HTML.
        return array_map(static fn($t) => BotPress_Markdown::from_html((string) $t), $templates);
    }

    public static function get_template(string $platform = 'default'): string {
        $templates = self::get_templates();
        if (!empty($templates[$platform])) {
            return $templates[$platform];
        }
        return $templates['default'] ?: self::default_template();
    }

    public static function save_templates(array $templates): void {
        $clean = static fn($t) => trim(BotPress_Markdown::from_html((string) $t));
        update_option('botpress_templates', [
            'default'  => $clean($templates['default'] ?? self::default_template()) ?: self::default_template(),
            'telegram' => $clean($templates['telegram'] ?? ''),
            'bale'     => $clean($templates['bale'] ?? ''),
        ]);
    }

    public function render(WP_Post $post): string {
        return strtr(BotPress_Markdown::from_html($this->template), $this->build_variables($post));
    }

    public static function preview(string $template): string {
        $sample = [
            '{title}'    => 'عنوان نمونه مقاله',
            '{excerpt}'  => 'این یک خلاصه نمونه برای پیش‌نمایش قالب پیام است...',
            '{url}'      => get_site_url() . '/sample-post/',
            '{date}'     => wp_date('Y/m/d'),
            '{author}'   => 'نویسنده',
            '{category}' => 'دسته‌بندی',
            '{tags}'     => 'تگ۱، تگ۲',
            '{site}'     => get_bloginfo('name'),
        ];

        return strtr(BotPress_Markdown::from_html($template), $sample);
    }

    private function build_variables(WP_Post $post): array {
        return [
            '{title}'    => BotPress_Markdown::escape($post->post_title),
            '{excerpt}'  => BotPress_Markdown::escape($this->get_excerpt($post)),
            '{url}'      => BotPress_Markdown::url((string) get_permalink($post->ID)),
            '{date}'     => mysql2date('Y/m/d', $post->post_date),
            '{author}'   => BotPress_Markdown::escape(get_the_author_meta('display_name', $post->post_author)),
            '{category}' => $this->get_category($post),
            '{tags}'     => $this->get_tags($post),
            '{site}'     => BotPress_Markdown::escape(get_bloginfo('name')),
        ];
    }

    private function get_excerpt(WP_Post $post, int $length = 300): string {
        $excerpt = $post->post_excerpt
            ?: wp_trim_words(wp_strip_all_tags($post->post_content), 50, '...');
        return mb_substr($excerpt, 0, $length);
    }

    private function get_category(WP_Post $post): string {
        $categories = wp_get_post_categories($post->ID, ['fields' => 'names']);
        return !empty($categories) ? BotPress_Markdown::escape($categories[0]) : '';
    }

    private function get_tags(WP_Post $post): string {
        $tags = get_the_tags($post->ID);
        if (!$tags) {
            return '';
        }
        return implode('، ', array_map(static fn($tag) => BotPress_Markdown::escape($tag->name), $tags));
    }

    public static function available_variables(): array {
        return [
            '{title}'    => 'عنوان مقاله',
            '{excerpt}'  => 'خلاصه مقاله',
            '{url}'      => 'لینک مقاله',
            '{date}'     => 'تاریخ انتشار',
            '{author}'   => 'نام نویسنده',
            '{category}' => 'دسته‌بندی',
            '{tags}'     => 'تگ‌ها',
            '{site}'     => 'نام سایت',
        ];
    }
}
