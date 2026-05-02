<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use SoftDeletes;

    protected $table = 'user_employees_device_tokens';

    protected $fillable = [
        'employee_id',
        'device_token',
        'device_identifier',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
