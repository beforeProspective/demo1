<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwtBlacklist extends Model
{
    protected $table = 'jwt_blacklist';

    public $timestamps = true;

    protected $fillable = [
        'token_id',
        'token',
        'expires_at',
        'user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'blacklisted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}