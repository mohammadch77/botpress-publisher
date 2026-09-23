<?php

use App\Models\Bot;
use App\Models\Tenant;
use App\Services\Admin\BotService;
use App\Services\Tenant\TenantContext;

beforeEach(function () {
    $this->service = new BotService();
    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);
});

it('round-trips an encrypted token', function () {
    $token = 'abc123:secret-telegram-token';

    $encrypted = $this->service->encryptToken($token);

    expect($encrypted)->not->toBe($token);

    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'Round Trip Bot',
        'token_encrypted' => $encrypted,
        'token_hash' => $this->service->hashToken($token),
        'status' => 'pending',
    ]);

    expect($this->service->decryptToken($bot))->toBe($token);
});

it('computes a sha256 token hash', function () {
    $token = 'my-token-value';

    expect($this->service->hashToken($token))->toBe(hash('sha256', $token));
});

it('creates a bot via the service without leaking the raw token', function () {
    $bot = $this->service->create([
        'platform' => 'bale',
        'name' => 'Service Bot',
        'token' => 'raw-bale-token',
    ]);

    expect($bot->token_encrypted)->not->toBe('raw-bale-token');
    expect($this->service->decryptToken($bot))->toBe('raw-bale-token');
    expect($bot->token_hash)->toBe(hash('sha256', 'raw-bale-token'));
});
