<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Views extends Model
{
    use SoftDeletes;

    protected $table = 'system_views_new';

    protected $fillable = [
        'name',
        'url',
        'modulo',
    ];
}
