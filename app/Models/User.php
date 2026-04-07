<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    use SoftDeletes;

    use Notifiable;

    protected $guard_name = 'web';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'employee',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'planta_empleado',
    ];

    public function getPlantaEmpleadoAttribute()
    {
        return $this->employee ? $this->employee->branch_office_id : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // public function hasPermissionToCustom($permissionName)
    // {
    //     // Si el permiso está revocado explícitamente
    //     $permission = Permission::where('name', $permissionName)->first();

    //     if ($permission && UserRevokedPermission::where('user_id', $this->id)
    //         ->where('permission_id', $permission->id)
    //         ->exists()) {
    //         return false;
    //     }

    //     // Caso normal: permisos por rol o usuario (Spatie)
    //     return $this->hasPermissionTo($permissionName);
    // }

    // public function revokePermission($permissionName)
    // {
    //     $permission = Permission::where('name', $permissionName)->first();
    //     if ($permission) {
    //         UserRevokedPermission::firstOrCreate([
    //             'user_id' => $this->id,
    //             'permission_id' => $permission->id
    //         ]);
    //     }
    // }

    // public function allowPermission($permissionName)
    // {
    //     $permission = Permission::where('name', $permissionName)->first();
    //     if ($permission) {
    //         UserRevokedPermission::where('user_id', $this->id)
    //             ->where('permission_id', $permission->id)
    //             ->delete();
    //     }
    // }

    public function branchOffices(){
        return $this->belongsToMany(
            BranchOffice::class,
            'branch_office_user',
            'user_id',
            'branch_office_id'
        );
    }

    public function branchOfficesUser($user){
        $boUser = DB::select(
            'SELECT bo.code, bo.id, bo.name
            FROM branch_office_user bou
            INNER JOIN branch_offices bo ON bo.id = bou.branch_office_id
            WHERE bou.user_id = ?',
            [$user->id]  
        );

        return $boUser;
    }

    public function permissionOverrides()
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    // Helper para obtener overrides en formato clave-valor para el frontend
    public function getPermissionOverridesAttribute()
    {
        return $this->permissionOverrides()
            ->with('permission')
            ->get()
            ->mapWithKeys(function ($override) {
                // Clave: "view.permission" o solo "permission" si no hay view
                $key = $override->view_name 
                    ? "{$override->permission->name}"
                    : $override->permission->name;
                
                return [$key => $override->is_allowed];
            })
            ->toArray();
    }
}
