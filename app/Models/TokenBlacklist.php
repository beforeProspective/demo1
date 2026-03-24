<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenBlacklist extends Model
{
    protected $table = 'token_blacklist';

    protected $fillable = [
        'token_jti',
        'user_id',
        'expires_at',
        'reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function isBlacklisted(string $jti): bool
    {
        return static::where('token_jti', $jti)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public static function addToBlacklist(string $jti, int $userId, \DateTime $expiresAt, string $reason = 'logout'): void
    {
        static::create([
            'token_jti' => $jti,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
            'reason' => $reason,
        ]);
    }

    public static function cleanExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
