<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->attributes->get('jwt_user');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '未认证的用户',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => '没有执行此操作的权限',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
