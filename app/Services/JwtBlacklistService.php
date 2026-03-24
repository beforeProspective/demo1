<?php

namespace App\Services;

use App\Models\JwtBlacklist;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtBlacklistService
{
    public function blacklistToken(string $token): bool
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();

            JwtBlacklist::create([
                'token_id' => $payload->get('jti'),
                'token' => $token,
                'expires_at' => \Carbon\Carbon::createFromTimestamp($payload->get('exp')),
                'user_id' => $payload->get('sub'),
            ]);

            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    public function isBlacklisted(string $tokenId): bool
    {
        return JwtBlacklist::where('token_id', $tokenId)->exists();
    }

    public function cleanupExpired(): int
    {
        return JwtBlacklist::where('expires_at', '<', now())->delete();
    }
}