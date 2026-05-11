<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(private readonly RolePermissionService $roles)
    {
    }

    public function getAllRolesWithPermissions(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'نقش‌ها با موفقیت دریافت شدند.',
            'data' => $this->roles->paginated($request),
        ]);
    }

    public function storeRolePermission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = $this->roles->createRole($data);

        return response()->json([
            'message' => 'نقش با موفقیت ایجاد شد.',
            'data' => $this->roles->formatRole($role),
        ], 201);
    }

    public function updateRolePermission(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = $this->roles->updateRole($role, $data);

        return response()->json([
            'message' => 'نقش با موفقیت ویرایش شد.',
            'data' => $this->roles->formatRole($role),
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        return response()->json([
            'message' => 'دسترسی‌ها با موفقیت دریافت شدند.',
            'data' => $this->roles->permissions(),
        ]);
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'message' => 'نقش‌ها با موفقیت دریافت شدند.',
            'data' => $this->roles->roles(),
        ]);
    }
}
