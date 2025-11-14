<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'inecofficer', 'voters'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            'manage.inec.officers',
            'manage.roles',
            'manage.permissions',
            'assign.permissions',
            'manage.elections',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo($permissions);
        }

        $voterPermissions = [
            'view.elections',
            'cast.vote',
            'view.results',
        ];
        foreach ($voterPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $voters = Role::where('name', 'voters')->first();
        if ($voters) {
            $voters->givePermissionTo($voterPermissions);
        }

        $inecOfficerPermissions = [
            'verify.voters',
            'view.results',
        ];
        foreach ($inecOfficerPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $inec = Role::where('name', 'inecofficer')->first();
        if ($inec) {
            $inec->givePermissionTo($inecOfficerPermissions);
        }
    }
}