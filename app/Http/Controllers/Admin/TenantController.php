<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenant\StoreTenantRequest;
use App\Http\Requests\Admin\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = $this->tenantService->list($request->only(['status', 'plan', 'search', 'per_page']));

        return response()->json([
            'data' => TenantResource::collection($tenants->items()),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return response()->json(['data' => new TenantResource($tenant)], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $tenant = $this->tenantService->withStats($tenant);

        return response()->json(['data' => new TenantResource($tenant)]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->update($tenant, $request->validated());

        return response()->json(['data' => new TenantResource($tenant)]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $this->tenantService->delete($tenant);

        return response()->json(null, 204);
    }
}
