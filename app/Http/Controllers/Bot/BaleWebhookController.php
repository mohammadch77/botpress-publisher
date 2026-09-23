<?php

namespace App\Http\Controllers\Bot;

use App\Enums\BotStatus;
use App\Enums\Platform;
use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\Bot\BotUpdateHandler;
use App\Services\Bot\UpdateParser;
use App\Services\Bot\WebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaleWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookVerifier $verifier,
        private readonly UpdateParser $parser,
        private readonly BotUpdateHandler $handler,
    ) {}

    public function handle(Request $request, string $botUuid): JsonResponse
    {
        $bot = Bot::withoutGlobalScopes()
            ->where('uuid', $botUuid)
            ->where('platform', Platform::Bale)
            ->where('status', BotStatus::Active)
            ->first();

        if (! $bot) {
            return response()->json(['ok' => true]);
        }

        if (! $this->verifier->verifyBale($request, $bot)) {
            return response()->json(['ok' => true]);
        }

        $update = $this->parser->parseBale($request->all());

        $this->handler->handle($bot, $update);

        return response()->json(['ok' => true]);
    }
}
