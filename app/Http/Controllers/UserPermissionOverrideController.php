<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Http\Request;

class UserPermissionOverrideController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserPermissionOverride $userPermissionOverride)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserPermissionOverride $userPermissionOverride)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserPermissionOverride $userPermissionOverride)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserPermissionOverride $userPermissionOverride)
    {
        //
    }

    public function saveOverrides(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.view_name' => 'nullable|string',
            'permissions.*.value' => 'required|boolean', // true = allow, false = deny
        ]);

        foreach ($request->permissions as $item) {
            if ($item['value'] === null) {
                // Si es null, eliminar override (volver a heredar del rol)
                $user->permissionOverrides()
                    ->where('permission_id', $item['permission_id'])
                    ->where('view_name', $item['view_name'] ?? null)
                    ->delete();
            } else {
                // Crear o actualizar override
                $user->permissionOverrides()->updateOrCreate(
                    [
                        'permission_id' => $item['permission_id'],
                        'view_name' => $item['view_name'] ?? null,
                    ],
                    [
                        'is_allowed' => $item['value'],
                        'reason' => $request->reason ?? null,
                    ]
                );
            }
        }

        // ✨ Importante: Recargar permisos en caché de Spatie si usas cache
        $user->load('permissions', 'roles.permissions');
        
        return back()->with('success', 'Overrides guardados');
    }
}
