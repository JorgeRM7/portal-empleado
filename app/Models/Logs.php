<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'action',
        'user_id',
        'table_name',
        'date',
        'old_data',
        'relationship_id'
    ];

    protected $casts = [
        'old_data' => 'array',
        'date' => 'datetime'
    ];

    public $timestamps = false;


}
