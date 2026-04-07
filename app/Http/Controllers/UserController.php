<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\RegistroGuardado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\BranchOffice;
use App\Models\Departments;
use App\Models\BranchOfficeUser;
use App\Models\Employee;
use App\Models\Views;
use Illuminate\Support\Facades\DB;
use App\Notifications\RegistroEditado;
use App\Notifications\RegistroEliminado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController
{
    public function index() {
        return Inertia::render('Users/Index');
    }

    public function create()
    {
        $branchOffices = BranchOffice::select('id', 'code')->get();
        
        return Inertia::render('Users/Create', [
            'branchOffices' => $branchOffices,
        ]);
    }

    public function store(Request $request) {

        $validate = $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8',
            'branchOffice'      => 'nullable|array',
            'asignPermissions'  => 'nullable|boolean',
        ]);

        $validate['password'] = Hash::make($validate['password']);

        $user = User::create([
            'username' => $validate['username'],
            'name'     => $validate['name'],
            'email'    => $validate['email'],
            'password' => $validate['password'],
        ]);

        $branchOffices = $request->input('branchOffice', []);

        foreach ($branchOffices as $branchOfficeId) {
            BranchOfficeUser::create([
                'user_id'         => $user->id,
                'branch_office_id'=> $branchOfficeId,
            ]);
        }

        $u = $request->user();
        $u->notify(new RegistroGuardado('Usuarios', 1));

        if($validate['asignPermissions']){
            return redirect()->route('users.edit', $user->id)->with('success','');
        }

        return redirect()->route('/users')->with('success','');
    }

    public function edit(User $user){
        $boUser = User::find($user->id)->branchOfficesUser($user);

        $branchOffices = BranchOffice::select('id', 'code')->get();

        $branchOfficesUser = collect($boUser)->pluck('id');

        $roles = Role::all()->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name,
        ]);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                ])->values(),
            ],
            'branchOffices' => $branchOffices,
            'branchOfficesUser' => $branchOfficesUser,
            'availableRoles' => $roles
        ]);
    }

    public function update(Request $request, User $user){
        
        $validate = $request->validate([
            'username' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|min:8',
            'branchOffice'      => 'nullable|array',
            'role' => 'nullable|exists:roles,id',
        ]);

        $user->update([
            'username' => $validate['username'],
            'name' => $validate['name'],
            'email' => $validate['email'],
        ]);

        if ($validate['password']) {
            $user->update(['password' => Hash::make($validate['password'])]);
        }

        if (isset($validate['role'])) {
            $role = Role::findById($validate['role'], 'web');
            $user->syncRoles([$role]);
        } else {
            $user->syncRoles([]);
        }

        foreach ($validate['branchOffice'] as $branchOfficeId) {
            BranchOfficeUser::updateOrCreate([
                'user_id'         => $user->id,
                'branch_office_id'=> $branchOfficeId,
            ], [
                'user_id'         => $user->id,
                'branch_office_id'=> $branchOfficeId,
            ]);
        }

        $u = $request->user();
        $u->notify(new RegistroEditado('Usuarios', 2));
    }

    public function destroy(User $user, Request $request){
        $user->delete();

        $u = $request->user();

        $u->notify(new RegistroEliminado('Usuarios', 3));
    }

    public function toggle(Request $request, Views $view)
    {
        //dd($request->all());
        $data = $request->validate([
            'user_id'    => 'required|integer|exists:users,id',
            'permission' => 'required|string',
            'assigned'   => 'required|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);
        $permission = Permission::where('name', $data['permission'])->firstOrFail();

        if ($data['assigned']) {
            // asignar permiso directo al usuario
            $user->givePermissionTo($permission);
        } else {
            // quitar permiso directo al usuario
            $user->revokePermissionTo($permission);
        }

        return back();
    }

    public function bulk(Request $request, Views $view)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
            'assigned' => 'required|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);

        $perms = Permission::whereIn('name', $data['permissions'])->get();

        if ($data['assigned']) {
            $user->givePermissionTo($perms);
        } else {
            $user->revokePermissionTo($perms);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back();
    }

    public function saveAll(Request $request, $userId)
    {
        //dd($request->all());
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*.view_id' => 'nullable|integer|exists:system_views,id',
            'permissions.*.permission' => 'required|string',
            'permissions.*.assigned' => 'required|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);
        
        // Separar permisos a asignar y a revocar
        $toAssign = [];
        $toRevoke = [];
        
        foreach ($data['permissions'] as $item) {
            $permission = Permission::where('name', $item['permission'])->first();
            if (!$permission) continue;
            
            if ($item['assigned']) {
                $toAssign[] = $permission;
            } else {
                $toRevoke[] = $permission;
            }
        }
        
        // Asignar permisos
        if (!empty($toAssign)) {
            $user->givePermissionTo($toAssign);
        }
        
        // Revocar permisos
        if (!empty($toRevoke)) {
            $user->revokePermissionTo($toRevoke);
        }
        
        // Limpiar cache de permisos
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        return back()->with('success', 'Permisos actualizados correctamente');
    }

    public function profile(Request $request){
        $user = User::findOrFail($request->user()->id);
        $idEmployee = Employee::select('id')->where('user_id', Auth::id())->get();
        return Inertia::render('User/Profile', [
            'user' => $user,
            'idEmployee' => $idEmployee
        ]);
    } 

    public function destroyMultiple(Request $request){

        //dd($request->all());

        $request->validate([
            'users' => 'required|array',
        ]);

        User::whereIn('id', $request->users)->delete();

        $u = $request->user();
        $u->notify(new RegistroEliminado('Usuarios', 3));
    }

    public function searchUser(Request $request)
    {
        $q = $request->q;

        return User::query()
            ->where(function ($query) use ($q) {
                $query->where('id', 'like', "%$q%")
                    ->orWhere('username', 'like', "%$q%");
            })
            ->limit(20)
            ->get(['id','username']);
    }

}
