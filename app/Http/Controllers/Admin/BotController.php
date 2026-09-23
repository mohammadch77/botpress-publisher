<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bot\StoreBotRequest;
use App\Http\Requests\Admin\Bot\UpdateBotRequest;
use App\Http\Resources\BotResource;
use App\Models\Bot;
use App\Services\Admin\BotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function __construct(private readonly BotService $botService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Bot::class);

        $bots = $this->botService->list($request->only(['platform', 'status', 'per_page']));

        return response()->json([
            'data' => BotResource::collection($bots->items()),
            'meta' => [
                'current_page' => $bots->currentPage(),
                'last_page' => $bots->lastPage(),
                'per_page' => $bots->perPage(),
                'total' => $bots->total(),
            ],
        ]);
    }

    public function store(StoreBotRequest $request): JsonResponse
    {
        $bot = $this->botService->create($request->validated());

        return response()->json(['data' => new BotResource($bot)], 201);
    }

    public function show(Bot $bot): JsonResponse
    {
        $this->authorize('view', $bot);

        return response()->json(['data' => new BotResource($bot)]);
    }

    public function update(UpdateBotRequest $request, Bot $bot): JsonResponse
    {
        $bot = $this->botService->update($bot, $request->validated());

        return response()->json(['data' => new BotResource($bot)]);
    }

    public function destroy(Bot $bot): JsonResponse
    {
        $this->authorize('delete', $bot);

        $this->botService->delete($bot);

        return response()->json(null, 204);
    }

    public function testConnection(Bot $bot): JsonResponse
    {
        $this->authorize('view', $bot);

        return response()->json($this->botService->testConnection($bot));
    }
}
