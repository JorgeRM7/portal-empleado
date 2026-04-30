<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use SoftDeletes;

    protected $table = 'user_device_tokens';

    protected $fillable = [
        'id_user',
        'device_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
