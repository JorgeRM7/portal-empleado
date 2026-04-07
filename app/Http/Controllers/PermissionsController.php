<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Models\Views;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

class PermissionsController
{

    private array $allowedActions = ['index', 'create', 'edit', 'delete', 'export', 'import', 'approve', 'reject', 'validate','multiple-approve', 'multiple-reject', 'multiple-delete', 'export-imss', 'export-sua', 'export-noi', 'export-cuentas', 'export-Empleados', 'multiple-edit'];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        

        return Inertia::render('Permissions/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

        $view = Views::findOrFail($request->view);

        $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
        $last = basename(trim($path, '/'));
        $base = Str::slug($last, '-'); 

        

        $existingNames = Permission::where('name', 'like', "{$base}.%")
            ->pluck('name')
            ->toArray();

        $permissionMatrix = collect($this->allowedActions)->map(function ($action) use ($base, $existingNames) {
            $name = "{$base}.{$action}";
            return [
                'action' => $action,
                'name'   => $name,
                'exists' => in_array($name, $existingNames, true),
            ];
        })->values();

        return Inertia::render('Permissions/Create', [
            'view' => [
                'id'   => $view->id,
                'name' => $view->name,
                'url'  => $view->url,
                'base' => $base,
            ],
            'permissionMatrix' => $permissionMatrix,
            'allowedActions'   => $this->allowedActions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'view_id' => 'required|exists:system_views_new,id',
            'actions' => 'required|array|min:1',
            'actions.*' => 'string',
            'guard_name' => 'nullable|string',
        ]);

        $view = Views::findOrFail($data['view_id']);

        $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
        $last = basename(trim($path, '/'));
        $base = Str::slug($last, '-');

        $guard = $data['guard_name'] ?? 'web';

        foreach ($data['actions'] as $action) {
            Permission::firstOrCreate([
                'name' => "{$base}.{$action}",
                'guard_name' => $guard,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.create', ['view' => $view->id]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Permission $permissions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permissions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permissions){
        $data = $request->validate([
                'view_id' => 'required|exists:system_views_new,id',
                'actions' => 'array',
                'actions.*' => 'string',
                'guard_name' => 'nullable|string',
            ]);

            $view = Views::findOrFail($data['view_id']);

            $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
            $last = basename(trim($path, '/'));
            $base = Str::slug($last, '-');

            $guard = $data['guard_name'] ?? 'web';

            $selectedActions = collect($data['actions'] ?? [])
                ->filter(fn ($a) => in_array($a, $this->allowedActions, true))
                ->values()
                ->all();

            $desiredNames = collect($selectedActions)
                ->map(fn ($a) => "{$base}.{$a}")
                ->all();

            $existing = Permission::where('guard_name', $guard)
                ->where('name', 'like', "{$base}.%")
                ->get();

            $existingNames = $existing->pluck('name')->all();

            $toCreate = array_values(array_diff($desiredNames, $existingNames));
            foreach ($toCreate as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
            }

            $toDelete = $existing->whereNotIn('name', $desiredNames);

            foreach ($toDelete as $perm) {
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                $perm->delete();
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('/permissions');
    }
    public function updateByView(Request $request)
    {
            
        }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permissions)
    {
        //
    }

    public function acceptAll(Request $request)
    {
        $data = $request->validate([
            'guard_name' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $guard = $data['guard_name'] ?? 'web';

        $user = User::findOrFail($data['user_id']);

        $permissions = Permission::where('guard_name', $guard)->get();

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back();
    }

    public function saveOverrides(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission_id' => 'required|exists:sys_permissions,id',
            'permissions.*.view_name' => 'nullable|string|max:255',
            'permissions.*.assigned' => 'required|boolean',
        ]);

        // dd($request->all());

        $updated = 0;
        $removed = 0;

        foreach ($request->permissions as $item) {
            $permission = Permission::find($item['permission_id']);
            
            if (!$permission) {
                continue;
            }
            
            $viewName = $item['view_name'] ?? null;
            
            // ✨ 1. Obtener valor base del rol (con try-catch)
            $baseValue = false;
            try {
                $baseValue = $user->hasPermissionTo($permission->name);
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $baseValue = false;
            }

            // ✨ 2. Buscar override existente
            $override = UserPermissionOverride::where('user_id', $user->id)
                ->where('permission_id', $item['permission_id'])
                ->where('view_name', $viewName)
                ->first();

            // ✨ 3. Lógica corregida:
            // - Si assigned == baseValue → Eliminar override (heredar del rol)
            // - Si assigned != baseValue → Crear/Actualizar override
            if ($item['assigned'] === $baseValue) {
                // El valor deseado es igual al del rol → No se necesita override
                if ($override) {
                    $override->delete();
                    $removed++;
                    Log::info("Override eliminado", [
                        'user_id' => $user->id,
                        'permission' => $permission->name,
                        'reason' => 'Valor igual al rol base'
                    ]);
                }
            } else {
                // El valor deseado es diferente al del rol → Se necesita override
                UserPermissionOverride::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'permission_id' => $item['permission_id'],
                        'view_name' => $viewName,
                    ],
                    [
                        'is_allowed' => $item['assigned'], // ✨ Importante: guardar el valor correcto
                        'reason' => $request->reason ?? null,
                    ]
                );
                $updated++;
                Log::info("Override creado/actualizado", [
                    'user_id' => $user->id,
                    'permission' => $permission->name,
                    'is_allowed' => $item['assigned']
                ]);
            }
        }

        // ✨ 4. Limpiar caché de permisos (CRUCIAL)
        $user->forgetCachedPermissions();
        
        // ✨ 5. También limpiar caché global de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', "Permisos actualizados: {$updated} overrides, {$removed} revertidos.");
    }
}
