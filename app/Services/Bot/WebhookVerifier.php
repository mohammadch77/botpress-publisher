<?php

namespace App\Services\Bot;

use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class WebhookVerifier
{
    public function verifyTelegram(Request $request, Bot $bot): bool
    {
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! $secret || ! $bot->webhook_secret) {
            return true;
        }

        return hash_equals($this->decryptSecret($bot->webhook_secret), $secret);
    }

    public function verifyBale(Request $request, Bot $bot): bool
    {
        return true;
    }

    private function decryptSecret(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }
}
