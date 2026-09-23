<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Destination;
use App\Models\Publication;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'tenants' => Tenant::query()->count(),
            'bots' => Bot::query()->count(),
            'destinations' => Destination::query()->count(),
            'publications' => Publication::query()->count(),
        ]);
    }
}
