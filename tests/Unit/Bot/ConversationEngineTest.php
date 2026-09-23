<?php

use App\Domain\Bot\DTOs\IncomingMessage;
use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Enums\ConversationStatus;
use App\Models\Bot;
use App\Models\ConversationSession;
use App\Models\PlatformIdentity;
use App\Models\Tenant;
use App\Services\Bot\ConversationEngine;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'chat' => ['id' => 555]]]),
    ]);

    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);

    $this->bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'TG Bot',
        'token_encrypted' => Crypt::encryptString('token'),
        'token_hash' => hash('sha256', 'token'),
        'status' => 'active',
    ]);

    $this->identity = PlatformIdentity::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'external_user_id' => '999',
    ]);

    $this->engine = app(ConversationEngine::class);
});

function makeUpdate(string $chatId): IncomingUpdate
{
    return new IncomingUpdate(
        platform: 'telegram',
        updateType: 'message',
        updateId: 1,
        message: new IncomingMessage(
            chatId: $chatId,
            chatType: 'private',
            fromId: '999',
            fromUsername: null,
            fromFirstName: null,
            messageType: 'text',
            text: 'hi',
            caption: null,
            mediaFileIds: null,
            messageId: 1,
            date: time(),
        ),
    );
}

it('creates a session for a new chat', function () {
    $update = makeUpdate('555');

    $this->engine->handle($this->bot, $this->identity, $update);

    $session = ConversationSession::withoutGlobalScopes()
        ->where('bot_id', $this->bot->id)
        ->where('chat_id', '555')
        ->first();

    expect($session)->not->toBeNull();
    expect($session->status)->toBe(ConversationStatus::Active);
});

it('reuses an existing active session', function () {
    $update = makeUpdate('555');

    $this->engine->handle($this->bot, $this->identity, $update);
    $first = ConversationSession::withoutGlobalScopes()->where('bot_id', $this->bot->id)->where('chat_id', '555')->first();

    $this->engine->handle($this->bot, $this->identity, $update);
    $count = ConversationSession::withoutGlobalScopes()->where('bot_id', $this->bot->id)->where('chat_id', '555')->count();

    expect($count)->toBe(1);
});

it('creates a new session when the previous one expired', function () {
    $update = makeUpdate('555');

    $this->engine->handle($this->bot, $this->identity, $update);
    $expired = ConversationSession::withoutGlobalScopes()->where('bot_id', $this->bot->id)->where('chat_id', '555')->first();
    $expired->update(['expires_at' => now()->subMinute()]);

    $this->engine->handle($this->bot, $this->identity, $update);
    $count = ConversationSession::withoutGlobalScopes()->where('bot_id', $this->bot->id)->where('chat_id', '555')->count();

    expect($count)->toBe(2);
});
