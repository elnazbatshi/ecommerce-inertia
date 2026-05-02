<?php

namespace App\Http\Controllers\Api;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function getAllRolesWithPermissions(Request $request): JsonResponse
    {
        $filters = $request->input('filters', []);
        $global = data_get($filters, 'global.value') ?: $request->string('search')->toString();
        $id = data_get($filters, 'id.constraints.0.value');
        $name = data_get($filters, 'name.constraints.0.value');
        $permission = data_get($filters, 'permissions.constraints.0.value');
        $sortField = in_array($request->input('sortField'), ['id', 'name'], true) ? $request->input('sortField') : 'id';
        $sortDirection = (int) $request->input('sortOrder', 1) === -1 ? 'desc' : 'asc';
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        $roles = Role::query()
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

        return response()->json([
            'message' => 'نقش‌ها با موفقیت دریافت شدند.',
            'data' => $roles,
        ]);
    }

    public function storeRolePermission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'message' => 'نقش با موفقیت ایجاد شد.',
            'data' => $this->formatRole($role->load('permissions:id,name')),
        ], 201);
    }

    public function updateRolePermission(Request $request, Role $role): JsonResponse
    {
        abort_if($role->name === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'message' => 'نقش با موفقیت ویرایش شد.',
            'data' => $this->formatRole($role->load('permissions:id,name')),
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        return response()->json([
            'message' => 'دسترسی‌ها با موفقیت دریافت شدند.',
            'data' => Permission::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Permission $permission) => $this->formatPermission($permission)),
        ]);
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'message' => 'نقش‌ها با موفقیت دریافت شدند.',
            'data' => Role::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => RoleName::labelFor($role->name),
                ]),
        ]);
    }

    private function formatRole(Role $role): array
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

    private function formatPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'label' => PermissionName::labelFor($permission->name),
        ];
    }

    private function enumValuesMatchingLabel(string $enumClass, string $search): array
    {
        $search = mb_strtolower($search);

        return collect($enumClass::cases())
            ->filter(fn ($case) => str_contains(mb_strtolower($case->label()), $search))
            ->map(fn ($case) => $case->value)
            ->values()
            ->all();
    }
}
