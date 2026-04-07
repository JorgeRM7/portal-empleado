<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{

    protected $table = 'tickets_desarrollo';
    
    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'status',
        'source'
    ];
}
