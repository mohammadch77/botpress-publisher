<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $botUuid): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
