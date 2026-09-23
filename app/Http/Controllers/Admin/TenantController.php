<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->tenantService->list()]);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return response()->json(['data' => $tenant], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json(['data' => $tenant]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->update($tenant, $request->validated());

        return response()->json(['data' => $tenant]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return response()->json(null, 204);
    }
}
