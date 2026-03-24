<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->attributes->get('jwt_user');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '未认证的用户',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => '权限不足',
                'error_code' => 'INSUFFICIENT_ROLE',
            ], 403);
        }

        return $next($request);
    }
}
