<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use SoftDeletes;
    protected $table = 'news';

    public function user()
    {
        return $this->belongsTo(User_nom::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function getIsLikedAttribute()
    {
        return $this->likes()->where('user_id', Auth::id())->exists();
    }
}
