<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JwtBlacklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error_code' => 'USER_NOT_FOUND'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active',
                'error_code' => 'ACCOUNT_INACTIVE'
            ], 403);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error_code' => 'INVALID_PASSWORD'
            ], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|string|in:user,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ], 201);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'permissions' => $this->getPermissionsByRole($user->role),
                ]
            ]
        ]);
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();

            JwtBlacklist::create([
                'token_id' => JWTAuth::getPayload($token)->get('jti'),
                'token' => $token,
                'expires_at' => Carbon::createFromTimestamp(JWTAuth::getPayload($token)->get('exp')),
                'user_id' => auth()->id(),
            ]);

            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token);

            JwtBlacklist::create([
                'token_id' => $payload->get('jti'),
                'token' => $token,
                'expires_at' => Carbon::createFromTimestamp($payload->get('exp')),
                'user_id' => auth()->id(),
            ]);

            $newToken = JWTAuth::refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    protected function getPermissionsByRole(string $role): array
    {
        $permissions = [
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

        return $permissions[$role] ?? [];
    }
}