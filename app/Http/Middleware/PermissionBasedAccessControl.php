<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionBasedAccessControl
{
    protected array $permissions = [
        'user' => [
            'profile.read',
            'profile.update',
        ],
        'admin' => [
            'profile.read',
            'profile.update',
            'users.read',
            'users.create',
            'users.update',
            'users.delete',
            'system.settings.read',
            'system.settings.update',
        ],
    ];

    public function handle(Request $request, Closure $next, string ...$requiredPermissions): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        $userPermissions = $this->permissions[$user->role] ?? [];

        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Required permission: ' . $permission,
                    'error_code' => 'PERMISSION_DENIED'
                ], 403);
            }
        }

        return $next($request);
    }
}