<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['提供的凭据不正确'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => '账户已被禁用',
                'error_code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        $tokens = $this->jwtService->generateTokenPair($user);

        return response()->json([
            'success' => true,
            'message' => '登录成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->getPermissions(),
                ],
                'tokens' => $tokens,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'is_active' => true,
        ]);

        $tokens = $this->jwtService->generateTokenPair($user);

        return response()->json([
            'success' => true,
            'message' => '注册成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->getPermissions(),
                ],
                'tokens' => $tokens,
            ],
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $tokens = $this->jwtService->refreshToken($request->refresh_token);

        if (!$tokens) {
            return response()->json([
                'success' => false,
                'message' => '无效的刷新令牌',
                'error_code' => 'INVALID_REFRESH_TOKEN',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => '令牌刷新成功',
            'data' => [
                'tokens' => $tokens,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => '未提供认证令牌',
                'error_code' => 'TOKEN_NOT_PROVIDED',
            ], 401);
        }

        $payload = $this->jwtService->getPayload($token);

        if ($payload) {
            $this->jwtService->invalidateToken($token, $payload->sub);
        }

        return response()->json([
            'success' => true,
            'message' => '登出成功',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('jwt_user');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->getPermissions(),
                ],
            ],
        ]);
    }
}
