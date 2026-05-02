<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionName::cases())->map(function (PermissionName $permission) {
            return Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        });

        $admin = Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => RoleName::MANAGER->value, 'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => RoleName::CUSTOMER->value, 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);
        $manager->syncPermissions($permissions->whereIn('name', [
            PermissionName::VIEW_PRODUCTS->value,
            PermissionName::CREATE_PRODUCTS->value,
            PermissionName::EDIT_PRODUCTS->value,
            PermissionName::VIEW_ORDERS->value,
            PermissionName::EDIT_ORDERS->value,
            PermissionName::VIEW_USERS->value,
            PermissionName::VIEW_ROLES->value,
            PermissionName::VIEW_ADMIN_DASHBOARD->value,
        ]));
        $customer->syncPermissions($permissions->whereIn('name', [
            PermissionName::VIEW_PRODUCTS->value,
            PermissionName::VIEW_ORDERS->value,
            PermissionName::CREATE_ORDERS->value,
            PermissionName::CANCEL_ORDERS->value,
            PermissionName::VIEW_CUSTOMER_DASHBOARD->value,
        ]));
    }
}
