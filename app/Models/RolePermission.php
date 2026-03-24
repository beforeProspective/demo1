<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'permission_id',
    ];

    public function permission()
    {
        return $this->belongsTo(UserPermission::class, 'permission_id');
    }

    public static function getPermissionsByRole(string $role): array
    {
        return static::where('role', $role)
            ->with('permission')
            ->get()
            ->pluck('permission.name')
            ->toArray();
    }
}
