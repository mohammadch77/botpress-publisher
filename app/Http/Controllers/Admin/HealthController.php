<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
