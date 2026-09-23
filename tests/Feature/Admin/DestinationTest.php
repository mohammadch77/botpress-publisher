<?php

use App\Models\Bot;
use App\Models\Destination;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WordPressSite;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
});

it('creates a wordpress_site destination with its child row', function () {
    $response = $this->actingAs($this->user)->postJson('/api/admin/destinations', [
        'type' => 'wordpress_site',
        'name' => 'My WP Site',
        'url' => 'https://example.com',
        'api_key' => 'secret-api-key',
    ]);

    $response->assertCreated()->assertJsonPath('data.type', 'wordpress_site');

    $destination = Destination::withoutGlobalScopes()->where('name', 'My WP Site')->firstOrFail();
    $site = WordPressSite::withoutGlobalScopes()->where('destination_id', $destination->id)->first();

    expect($site)->not->toBeNull();
    expect($site->url)->toBe('https://example.com');
    expect($site->api_key_encrypted)->not->toBe('secret-api-key');
});

it('creates a telegram_channel destination with its child row', function () {
    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'Channel Bot',
        'token_encrypted' => encrypt('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/admin/destinations', [
        'type' => 'telegram_channel',
        'name' => 'My Channel',
        'bot_id' => $bot->id,
        'external_chat_id' => '-100123456789',
    ]);

    $response->assertCreated()->assertJsonPath('data.type', 'telegram_channel');

    $destination = Destination::withoutGlobalScopes()->where('name', 'My Channel')->firstOrFail();

    expect($destination->telegramDestination()->withoutGlobalScopes()->first())->not->toBeNull();
    expect($destination->telegramDestination()->withoutGlobalScopes()->first()->external_chat_id)->toBe('-100123456789');
});
