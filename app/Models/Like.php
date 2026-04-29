<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $table = 'likes';
    protected $fillable = [
        'post_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class, 
            User::class, 
            'id',      
            'id', 
            'user_id', 
            'id'       
        );
    }
}
