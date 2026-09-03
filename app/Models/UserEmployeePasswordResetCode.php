<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEmployeePasswordResetCode extends Model
{
    protected $table = 'user_employees_password_reset_codes';

    protected $fillable = [
        'employee_id',
        'user_id',
        'email',
        'code',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}