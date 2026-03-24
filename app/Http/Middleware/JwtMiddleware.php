<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => '未提供认证令牌',
                'error_code' => 'TOKEN_NOT_PROVIDED',
            ], 401);
        }

        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => '无效或已过期的令牌',
                'error_code' => 'INVALID_TOKEN',
            ], 401);
        }

        if ($payload->type !== 'access') {
            return response()->json([
                'success' => false,
                'message' => '令牌类型错误，请使用访问令牌',
                'error_code' => 'INVALID_TOKEN_TYPE',
            ], 401);
        }

        $user = User::find($payload->sub);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '用户不存在',
                'error_code' => 'USER_NOT_FOUND',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => '账户已被禁用',
                'error_code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        $request->attributes->set('jwt_user', $user);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
