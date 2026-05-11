<?php

namespace App\Http\Controllers;

use App\Http\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AccessController extends Controller
{
    public function __construct(private readonly AccessService $access)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Access/Index', [
            'roles' => $this->access->paginatedRoles($request),
            'permissions' => $this->access->permissionOptions(),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->access->createRole($data);

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->access->updateRole($role, $data);

        return back()->with('success', 'Role updated successfully.');
    }
}
