<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json(['user' => $user->only(['uuid', 'name', 'email'])]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->only(['uuid', 'name', 'email'])]);
    }
}
