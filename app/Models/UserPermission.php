<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function roles()
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }
}
