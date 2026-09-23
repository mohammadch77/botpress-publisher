<?php

namespace App\Services\Bot;

use App\Domain\Bot\Contracts\BotDriverInterface;
use App\Enums\Platform;
use App\Models\Bot;
use App\Services\Bot\Drivers\BaleBotDriver;
use App\Services\Bot\Drivers\TelegramBotDriver;
use Illuminate\Support\Facades\Crypt;

class BotDriverFactory
{
    public function make(Bot $bot): BotDriverInterface
    {
        $token = $this->decryptToken($bot->token_encrypted);

        return match ($bot->platform) {
            Platform::Telegram => new TelegramBotDriver($token),
            Platform::Bale => new BaleBotDriver($token),
        };
    }

    private function decryptToken(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }
}
