<?php

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

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
    expect(Crypt::decryptString($bot->token_encrypted))->toBe('123456:AAExampleTelegramToken');
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

it('reports a failed connection when the bot API is unreachable', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
    ]);

    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'Stub Bot',
        'token_encrypted' => Crypt::encryptString('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/admin/bots/{$bot->uuid}/test-connection");

    $response->assertOk()->assertJson([
        'connected' => false,
        'error' => 'Connection failed',
    ]);

    expect($bot->refresh()->status)->toBe(BotStatus::Error);
});

it('marks the bot active on a successful connection', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => ['id' => 42, 'username' => 'my_bot', 'first_name' => 'My Bot'],
        ]),
    ]);

    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'Stub Bot',
        'token_encrypted' => Crypt::encryptString('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/admin/bots/{$bot->uuid}/test-connection");

    $response->assertOk()->assertJson([
        'connected' => true,
        'username' => 'my_bot',
    ]);

    expect($bot->refresh()->status)->toBe(BotStatus::Active);
});
