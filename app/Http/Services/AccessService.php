<?php

namespace App\Http\Services;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessService
{
    public function paginatedRoles(Request $request)
    {
        return Role::query()
            ->with(['permissions:id,name'])
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => RoleName::labelFor($role->name),
                'permissions' => $role->permissions->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => PermissionName::labelFor($permission->name),
                ])->values(),
            ]);
    }

    public function permissionOptions()
    {
        return Permission::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => PermissionName::labelFor($permission->name),
            ]);
    }

    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        abort_if($role->name === 'admin', 403);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->refresh();
    }
}
