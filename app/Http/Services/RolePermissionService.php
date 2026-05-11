<?php

namespace App\Http\Services;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    public function paginated(Request $request)
    {
        $filters = $request->input('filters', []);
        $global = data_get($filters, 'global.value') ?: $request->string('search')->toString();
        $id = data_get($filters, 'id.constraints.0.value');
        $name = data_get($filters, 'name.constraints.0.value');
        $permission = data_get($filters, 'permissions.constraints.0.value');
        $sortField = in_array($request->input('sortField'), ['id', 'name'], true) ? $request->input('sortField') : 'id';
        $sortDirection = (int) $request->input('sortOrder', 1) === -1 ? 'desc' : 'asc';
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        return Role::query()
            ->with(['permissions:id,name'])
            ->when($global, function ($query, string $search) {
                $matchingRoles = $this->enumValuesMatchingLabel(RoleName::class, $search);
                $matchingPermissions = $this->enumValuesMatchingLabel(PermissionName::class, $search);

                $query->where(function ($query) use ($search, $matchingRoles, $matchingPermissions) {
                    $query->where('id', $search)
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'like', "%{$search}%"))
                        ->when($matchingRoles, fn ($query) => $query->orWhereIn('name', $matchingRoles))
                        ->when($matchingPermissions, fn ($query) => $query->orWhereHas(
                            'permissions',
                            fn ($permissionQuery) => $permissionQuery->whereIn('name', $matchingPermissions)
                        ));
                });
            })
            ->when($id, fn ($query, $value) => $query->where('id', $value))
            ->when($name, function ($query, $value) {
                $matchingRoles = $this->enumValuesMatchingLabel(RoleName::class, $value);

                $query->where(function ($query) use ($value, $matchingRoles) {
                    $query->where('name', 'like', "%{$value}%")
                        ->when($matchingRoles, fn ($query) => $query->orWhereIn('name', $matchingRoles));
                });
            })
            ->when($permission, function ($query, $value) {
                $matchingPermissions = $this->enumValuesMatchingLabel(PermissionName::class, $value);

                $query->whereHas('permissions', function ($permissionQuery) use ($value, $matchingPermissions) {
                    $permissionQuery->where('name', 'like', "%{$value}%")
                        ->when($matchingPermissions, fn ($permissionQuery) => $permissionQuery->orWhereIn('name', $matchingPermissions));
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->through(fn (Role $role) => $this->formatRole($role));
    }

    public function permissions()
    {
        return Permission::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => $this->formatPermission($permission));
    }

    public function roles()
    {
        return Role::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => RoleName::labelFor($role->name),
            ]);
    }

    public function createRole(array $data): Role
    {
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->load('permissions:id,name');
    }

    public function updateRole(Role $role, array $data): Role
    {
        abort_if($role->name === 'admin', 403);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->load('permissions:id,name');
    }

    public function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => RoleName::labelFor($role->name),
            'permissions' => $role->permissions
                ->map(fn (Permission $permission) => $this->formatPermission($permission))
                ->values(),
        ];
    }

    public function formatPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'label' => PermissionName::labelFor($permission->name),
        ];
    }

    public function enumValuesMatchingLabel(string $enumClass, string $search): array
    {
        $search = mb_strtolower($search);

        return collect($enumClass::cases())
            ->filter(fn ($case) => str_contains(mb_strtolower($case->label()), $search))
            ->map(fn ($case) => $case->value)
            ->values()
            ->all();
    }
}
