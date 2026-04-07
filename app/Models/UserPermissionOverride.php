<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermissionOverride extends Model
{
    protected $fillable = ['user_id', 'permission_id', 'view_name', 'is_allowed', 'reason'];
    
    protected $casts = [
        'is_allowed' => 'boolean',
    ];
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function permission() {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class);
    }
}
