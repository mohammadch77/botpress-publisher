<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Destination\AddDestinationUserRequest;
use App\Http\Requests\Admin\Destination\StoreDestinationRequest;
use App\Http\Requests\Admin\Destination\UpdateDestinationRequest;
use App\Http\Resources\DestinationResource;
use App\Http\Resources\UserResource;
use App\Models\Destination;
use App\Services\Admin\DestinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function __construct(private readonly DestinationService $destinationService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Destination::class);

        $destinations = $this->destinationService->list($request->only(['type', 'status', 'per_page']));

        return response()->json([
            'data' => DestinationResource::collection($destinations->items()),
            'meta' => [
                'current_page' => $destinations->currentPage(),
                'last_page' => $destinations->lastPage(),
                'per_page' => $destinations->perPage(),
                'total' => $destinations->total(),
            ],
        ]);
    }

    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $destination = $this->destinationService->create($request->validated());

        return response()->json(['data' => new DestinationResource($destination)], 201);
    }

    public function show(Destination $destination): JsonResponse
    {
        $this->authorize('view', $destination);

        $destination->load(['wordpressSite', 'telegramDestination', 'baleDestination']);

        return response()->json(['data' => new DestinationResource($destination)]);
    }

    public function update(UpdateDestinationRequest $request, Destination $destination): JsonResponse
    {
        $destination = $this->destinationService->update($destination, $request->validated());

        return response()->json(['data' => new DestinationResource($destination)]);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $this->authorize('delete', $destination);

        $this->destinationService->delete($destination);

        return response()->json(null, 204);
    }

    public function testConnection(Destination $destination): JsonResponse
    {
        $this->authorize('view', $destination);

        return response()->json($this->destinationService->testConnection($destination));
    }

    public function users(Destination $destination): JsonResponse
    {
        $this->authorize('view', $destination);

        $destination->load('destinationUsers.user');

        return response()->json([
            'data' => UserResource::collection($destination->destinationUsers->pluck('user')),
        ]);
    }

    public function addUser(AddDestinationUserRequest $request, Destination $destination): JsonResponse
    {
        $this->destinationService->addUser(
            $destination,
            $request->validated('user_id'),
            $request->validated('permission', 'publish'),
            $request->user()?->id,
        );

        return response()->json(['message' => 'User added to destination.'], 201);
    }

    public function removeUser(Request $request, Destination $destination, int $user): JsonResponse
    {
        $this->authorize('update', $destination);

        $this->destinationService->removeUser($destination, $user);

        return response()->json(null, 204);
    }
}
