<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusReason extends Model
{
    use SoftDeletes;

    protected $table = 'employee_status_reasons';

    protected $fillable = [
        'name',
        'type',
    ];
}
