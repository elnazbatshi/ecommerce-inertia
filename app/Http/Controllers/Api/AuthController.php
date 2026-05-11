<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Registered successfully.',
            'data' => $this->tokens->tokenPayload($this->tokens->register($request->validated())),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->tokens->attempt($request->validated('phone'), $request->validated('password'));

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => $this->tokens->tokenPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($request->user())->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
