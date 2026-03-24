<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = [
            ['name' => 'view_users', 'display_name' => '查看用户', 'description' => '允许查看用户列表'],
            ['name' => 'create_users', 'display_name' => '创建用户', 'description' => '允许创建新用户'],
            ['name' => 'edit_users', 'display_name' => '编辑用户', 'description' => '允许编辑用户信息'],
            ['name' => 'delete_users', 'display_name' => '删除用户', 'description' => '允许删除用户'],
            ['name' => 'view_reports', 'display_name' => '查看报告', 'description' => '允许查看系统报告'],
            ['name' => 'manage_settings', 'display_name' => '管理设置', 'description' => '允许管理系统设置'],
            ['name' => 'manage_users', 'display_name' => '管理用户', 'description' => '允许管理所有用户'],
            ['name' => 'view_own_profile', 'display_name' => '查看个人资料', 'description' => '允许查看自己的个人资料'],
            ['name' => 'edit_own_profile', 'display_name' => '编辑个人资料', 'description' => '允许编辑自己的个人资料'],
        ];

        foreach ($permissions as $permission) {
            UserPermission::create($permission);
        }

        $userPermissions = UserPermission::whereIn('name', [
            'view_own_profile',
            'edit_own_profile',
            'view_reports',
        ])->pluck('id');

        foreach ($userPermissions as $permissionId) {
            RolePermission::create([
                'role' => 'user',
                'permission_id' => $permissionId,
            ]);
        }

        $adminPermissions = UserPermission::pluck('id');
        foreach ($adminPermissions as $permissionId) {
            RolePermission::create([
                'role' => 'admin',
                'permission_id' => $permissionId,
            ]);
        }

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => false,
        ]);
    }
}
