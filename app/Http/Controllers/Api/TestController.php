<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function publicEndpoint(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '这是一个公开的API端点',
            'data' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function protectedEndpoint(Request $request): JsonResponse
    {
        $user = $request->attributes->get('jwt_user');

        return response()->json([
            'success' => true,
            'message' => '这是一个受保护的API端点',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function userOnlyEndpoint(Request $request): JsonResponse
    {
        $user = $request->attributes->get('jwt_user');

        return response()->json([
            'success' => true,
            'message' => '这是一个普通用户专属的API端点',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function adminOnlyEndpoint(Request $request): JsonResponse
    {
        $user = $request->attributes->get('jwt_user');

        return response()->json([
            'success' => true,
            'message' => '这是一个管理员专属的API端点',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'admin_data' => [
                    'total_users' => \App\Models\User::count(),
                    'active_users' => \App\Models\User::where('is_active', true)->count(),
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function permissionRequiredEndpoint(Request $request): JsonResponse
    {
        $user = $request->attributes->get('jwt_user');

        return response()->json([
            'success' => true,
            'message' => '这是一个需要特定权限的API端点',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'permissions' => $user->getPermissions(),
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
