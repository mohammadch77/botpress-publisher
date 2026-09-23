<?php

use App\Models\Bot;
use App\Models\Tenant;
use App\Services\Bot\BotDriverFactory;
use App\Services\Bot\Drivers\BaleBotDriver;
use App\Services\Bot\Drivers\TelegramBotDriver;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);
    $this->factory = new BotDriverFactory;
});

it('builds a telegram driver for telegram bots', function () {
    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'TG Bot',
        'token_encrypted' => Crypt::encryptString('tg-token'),
        'token_hash' => hash('sha256', 'tg-token'),
        'status' => 'pending',
    ]);

    expect($this->factory->make($bot))->toBeInstanceOf(TelegramBotDriver::class);
});

it('builds a bale driver for bale bots', function () {
    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'bale',
        'name' => 'Bale Bot',
        'token_encrypted' => Crypt::encryptString('bale-token'),
        'token_hash' => hash('sha256', 'bale-token'),
        'status' => 'pending',
    ]);

    expect($this->factory->make($bot))->toBeInstanceOf(BaleBotDriver::class);
});

it('decrypts the token before constructing the driver', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => ['id' => 1, 'username' => 'bot', 'first_name' => 'Bot'],
        ]),
    ]);

    $bot = Bot::create([
        'tenant_id' => $this->tenant->id,
        'platform' => 'telegram',
        'name' => 'TG Bot',
        'token_encrypted' => Crypt::encryptString('plain-token'),
        'token_hash' => hash('sha256', 'plain-token'),
        'status' => 'pending',
    ]);

    $this->factory->make($bot)->getMe();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/botplain-token/getMe');
    });
});
