<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleRbacMatrixController extends Controller
{
    public function index(): RedirectResponse
    {
        $this->authorizeMatrix();

        $role = Role::query()->where('guard_name', 'admin')->orderBy('name')->firstOrFail();

        return redirect()->route('admin.rbac.matrix.edit', $role);
    }

    public function edit(Role $role): Response
    {
        $this->authorizeMatrix();
        abort_unless($role->guard_name === 'admin', 404);

        $this->ensureManagedPermissionsExist();

        $role->load('permissions');
        $assigned = $role->permissions->pluck('name')->all();

        $roles = Role::query()->where('guard_name', 'admin')->orderBy('name')->get();

        return response()->view('admin.rbac.matrix', [
            'role' => $role,
            'roles' => $roles,
            'screens' => AdminRbac::managedScreens(),
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeMatrix();
        abort_unless($role->guard_name === 'admin', 404);

        $this->ensureAllCrudPermissionsExist();

        $managedFlat = AdminRbac::allManagedPermissionNames();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($managedFlat)],
        ]);

        $incoming = array_values(array_unique($validated['permissions'] ?? []));

        DB::transaction(function () use ($role, $managedFlat, $incoming): void {
            $role->refresh();
            $role->load('permissions');
            $keep = $role->permissions
                ->pluck('name')
                ->filter(static fn (string $n): bool => ! in_array($n, $managedFlat, true))
                ->values()
                ->all();

            $role->syncPermissions(array_merge($keep, $incoming));
        });

        return redirect()
            ->route('admin.rbac.matrix.edit', $role)
            ->with('status', 'Permisos del rol actualizados.');
    }

    private function authorizeMatrix(): void
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);
        abort_unless($user->can('roles.edit'), 403);
    }

    private function ensureManagedPermissionsExist(): void
    {
        foreach (AdminRbac::allManagedPermissionNames() as $name) {
            Permission::findOrCreate($name, 'admin');
        }
    }

    private function ensureAllCrudPermissionsExist(): void
    {
        foreach (AdminRbac::allCrudPermissionNames() as $name) {
            Permission::findOrCreate($name, 'admin');
        }
    }
}
