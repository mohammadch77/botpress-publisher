<?php

use App\Models\Bot;
use App\Models\ConversationSession;
use App\Models\PlatformIdentity;
use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::fake([
        'tapi.bale.ai/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'chat' => ['id' => 111]]]),
    ]);

    $this->tenant = Tenant::factory()->create();
    $this->bot = Bot::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'bale',
        'name' => 'Bale Bot',
        'token_encrypted' => Crypt::encryptString('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'active',
    ]);
});

it('returns 200 for an unknown bot uuid', function () {
    $response = $this->postJson('/webhook/bale/'.Str::uuid(), [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'date' => time(), 'chat' => ['id' => 111, 'type' => 'private'], 'from' => ['id' => 222], 'text' => 'hi'],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);
});

it('returns 200 for a valid bot', function () {
    $response = $this->postJson("/webhook/bale/{$this->bot->uuid}", [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'date' => time(), 'chat' => ['id' => 111, 'type' => 'private'], 'from' => ['id' => 222], 'text' => 'hi'],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);
});

it('parses the payload and creates a session', function () {
    $this->postJson("/webhook/bale/{$this->bot->uuid}", [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'date' => time(), 'chat' => ['id' => 111, 'type' => 'private'], 'from' => ['id' => 222], 'text' => 'hi'],
    ]);

    $session = ConversationSession::withoutGlobalScopes()->where('bot_id', $this->bot->id)->where('chat_id', '111')->first();

    expect($session)->not->toBeNull();
});

it('creates a platform identity for a new user', function () {
    $this->postJson("/webhook/bale/{$this->bot->uuid}", [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'date' => time(), 'chat' => ['id' => 111, 'type' => 'private'], 'from' => ['id' => 222], 'text' => 'hi'],
    ]);

    $identity = PlatformIdentity::withoutGlobalScopes()->where('platform', 'bale')->where('external_user_id', '222')->first();

    expect($identity)->not->toBeNull();
});
