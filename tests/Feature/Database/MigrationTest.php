<?php

use Illuminate\Support\Facades\Schema;

it('creates all expected tables', function () {
    $tables = [
        'tenants',
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'user_roles',
        'personal_access_tokens',
        'platform_identities',
        'bots',
        'conversation_sessions',
        'conversation_history',
        'destinations',
        'wordpress_sites',
        'telegram_destinations',
        'bale_destinations',
        'destination_users',
        'wp_post_types',
        'wp_taxonomies',
        'wp_categories',
        'wp_tags',
        'wp_authors',
        'wp_elementor_templates',
        'wp_content_profiles',
        'assets',
        'asset_uploads',
        'contents',
        'content_assets',
        'publications',
        'wordpress_publication_data',
        'telegram_publication_data',
        'bale_publication_data',
        'publication_logs',
        'audit_logs',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Expected table [{$table}] to exist.");
    }
});
