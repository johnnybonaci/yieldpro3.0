<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::create(['name' => 'profile']);
        Permission::create(['name' => 'leads']);
        Permission::create(['name' => 'calls']);
        Permission::create(['name' => 'campaign']);
        Permission::create(['name' => 'jornaya']);
        Permission::create(['name' => 'phone_room']);
        Permission::create(['name' => 'performance']);
        Permission::create(['name' => 'users']);
        Permission::create(['name' => 'settings']);
        Permission::create(['name' => 'roles']);
        Permission::create(['name' => 'reports_phone_room']);

        $clientRole = Role::create(['name' => 'client']);
        $userRole = Role::create(['name' => 'user']);
        $adminRole = Role::create(['name' => 'admin']);
        $superAdminRole = Role::create(['name' => 'super_admin']);

        $clientRole->givePermissionTo([
            'profile',
            'leads',
            'calls',
            'jornaya',
        ]);

        $userRole->givePermissionTo([
            'profile',
            'leads',
            'calls',
            'campaign',
        ]);

        $adminRole->givePermissionTo([
            'profile',
            'leads',
            'calls',
            'campaign',
            'jornaya',
            'phone_room',
            'performance',
        ]);

        $superAdminRole->givePermissionTo([
            'profile',
            'leads',
            'calls',
            'campaign',
            'jornaya',
            'phone_room',
            'performance',
            'users',
            'settings',
            'roles',
            'reports_phone_room',
        ]);

        User::all()->map(function ($user) {
            $user->assignRole('client');
        });
        User::whereBetween('id', [4, 12])->get()->collect()->map(function ($user) {
            $user->syncRoles('admin');
        });
        User::whereBetween('id', [1, 4])->get()->collect()->map(function ($user) {
            $user->syncRoles('super_admin');
        });
    }
}
