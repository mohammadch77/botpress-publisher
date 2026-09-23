<?php

use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
});

it('creates a bot and encrypts the token', function () {
    $response = $this->actingAs($this->user)->postJson('/api/admin/bots', [
        'platform' => 'telegram',
        'name' => 'My Bot',
        'token' => '123456:AAExampleTelegramToken',
    ]);

    $response->assertCreated();
    $response->assertJsonMissingPath('data.token');
    $response->assertJsonMissingPath('data.token_encrypted');
    $response->assertJsonPath('data.token_preview', '****oken');

    $bot = Bot::withoutGlobalScopes()->where('name', 'My Bot')->firstOrFail();
    expect($bot->token_encrypted)->not->toBe('123456:AAExampleTelegramToken');
    expect(\Illuminate\Support\Facades\Crypt::decryptString($bot->token_encrypted))->toBe('123456:AAExampleTelegramToken');
    expect($bot->token_hash)->toBe(hash('sha256', '123456:AAExampleTelegramToken'));
});

it('never exposes the raw token in list responses', function () {
    $this->actingAs($this->user)->postJson('/api/admin/bots', [
        'platform' => 'bale',
        'name' => 'Bale Bot',
        'token' => 'super-secret-token',
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/admin/bots');

    $response->assertOk();
    $body = $response->getContent();
    expect($body)->not->toContain('super-secret-token');
});

it('returns the not-implemented stub for test-connection', function () {
    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'Stub Bot',
        'token_encrypted' => encrypt('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/admin/bots/{$bot->uuid}/test-connection");

    $response->assertOk()->assertJson([
        'connected' => false,
        'message' => 'Not implemented yet',
    ]);
});
