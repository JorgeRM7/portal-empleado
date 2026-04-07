<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NetsuiteClass extends Model
{
    use HasFactory;

    protected $table = 'netsuite_class';

    protected $fillable = [
        'id',
        'external_id',
        'name',
    ];
}







