<?php

namespace App\Services;

use App\Models\TokenBlacklist;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Str;

class JwtService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $accessTokenTTL;
    private int $refreshTokenTTL;

    public function __construct()
    {
        $this->secretKey = config('app.key');
        $this->accessTokenTTL = config('jwt.access_token_ttl', 3600);
        $this->refreshTokenTTL = config('jwt.refresh_token_ttl', 604800);
    }

    public function generateAccessToken(User $user): array
    {
        $now = time();
        $jti = Str::random(32);
        $expiresAt = $now + $this->accessTokenTTL;

        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $expiresAt,
            'jti' => $jti,
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'permissions' => $user->getPermissions(),
            'type' => 'access',
        ];

        return [
            'token' => JWT::encode($payload, $this->secretKey, $this->algorithm),
            'jti' => $jti,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'expires_in' => $this->accessTokenTTL,
        ];
    }

    public function generateRefreshToken(User $user): array
    {
        $now = time();
        $jti = Str::random(32);
        $expiresAt = $now + $this->refreshTokenTTL;

        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $expiresAt,
            'jti' => $jti,
            'sub' => $user->id,
            'type' => 'refresh',
        ];

        return [
            'token' => JWT::encode($payload, $this->secretKey, $this->algorithm),
            'jti' => $jti,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'expires_in' => $this->refreshTokenTTL,
        ];
    }

    public function generateTokenPair(User $user): array
    {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $accessToken['token'],
            'refresh_token' => $refreshToken['token'],
            'token_type' => 'Bearer',
            'access_expires_in' => $accessToken['expires_in'],
            'refresh_expires_in' => $refreshToken['expires_in'],
        ];
    }

    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            if (TokenBlacklist::isBlacklisted($decoded->jti)) {
                return null;
            }

            return $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function refreshToken(string $refreshToken): ?array
    {
        $decoded = $this->validateToken($refreshToken);

        if (!$decoded || $decoded->type !== 'refresh') {
            return null;
        }

        $user = User::find($decoded->sub);

        if (!$user || !$user->is_active) {
            return null;
        }

        TokenBlacklist::addToBlacklist(
            $decoded->jti,
            $user->id,
            new \DateTime('@' . $decoded->exp),
            'refresh'
        );

        return $this->generateTokenPair($user);
    }

    public function invalidateToken(string $token, int $userId): bool
    {
        $decoded = $this->validateToken($token);

        if (!$decoded) {
            return false;
        }

        TokenBlacklist::addToBlacklist(
            $decoded->jti,
            $userId,
            new \DateTime('@' . $decoded->exp),
            'logout'
        );

        return true;
    }

    public function getPayload(string $token): ?object
    {
        return $this->validateToken($token);
    }
}
