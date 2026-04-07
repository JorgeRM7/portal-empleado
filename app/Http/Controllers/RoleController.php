<?php

namespace App\Http\Controllers;

use App\Models\Views;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController
{
    public function index()
    {
        $roles = Role::all();
        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }
    private function getViewsPermissionsForRole(Role $role): array
    {
        return Views::all()->map(function ($view) use ($role) {
            $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
            $last = basename(trim($path, '/'));
            $base = \Illuminate\Support\Str::slug($last, '-');

            $permissions = Permission::where('name', 'like', "{$base}.%")
                ->where('guard_name', $role->guard_name)
                ->orderBy('name')
                ->get()
                ->map(fn($perm) => [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'assigned' => $role->hasPermissionTo($perm),
                ]);

            return [
                'id' => $view->id,
                'name' => $view->name,
                'url' => $view->url,
                'base' => $base,
                'permissions' => $permissions,
            ];
        })->toArray();
    }
    public function edit(Role $role)
    {
        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ],
            'availableGuards' => ['web', 'api'], // O los que uses
            'views' => $this->getViewsPermissionsForRole($role),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', $role->guard_name)
                    ->ignore($role->id),
            ],
            'permissions' => 'array'
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role->users->each(fn($user) => $user->forgetCachedPermissions());

        return back()->with('success', "Rol '{$role->name}' actualizado correctamente.");
    }

    public function destroy(Role $role)
    {
        $protectedRoles = ['SUPER ADMINISTRADOR', 'ADMINISTRADOR'];
        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', "No se puede eliminar el rol '{$role->name}' porque es un rol del sistema.");
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return back()->with('error', 
                "No se puede eliminar el rol '{$role->name}' porque tiene {$usersCount} usuario(s) asignado(s). " .
                "Reasigna o elimina los usuarios primero."
            );
        }

        $roleName = $role->name;

        $role->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info("Rol eliminado", [
            'role_id' => $role->id,
            'role_name' => $roleName,
            'deleted_by' => Auth::id(),
        ]);

        return redirect()->route('/roles')
            ->with('success', "Rol '{$roleName}' eliminado correctamente.");
    }
    
    public function forceDestroy(Request $request, Role $role)
    {
        $request->validate([
            'confirm' => 'required|in:yes',
            'action' => 'required|in:reassign,remove',
            'new_role_id' => 'required_if:action,reassign|exists:roles,id',
        ]);

        if ($request->action === 'reassign') {
            $newRole = Role::find($request->new_role_id);
            $role->users()->each(fn($user) => $user->syncRoles([$newRole]));
        } else {
            $role->users()->each(fn($user) => $user->syncRoles([]));
        }

        $roleName = $role->name;
        $role->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Log::warning("Rol forzado a eliminar", [
            'role_id' => $role->id,
            'role_name' => $roleName,
            'action' => $request->action,
            'deleted_by' => Auth::id(),
        ]);

        return redirect()->route('/roles')
            ->with('success', "Rol '{$roleName}' eliminado (usuarios {$request->action}ados).");
    }

    
}
