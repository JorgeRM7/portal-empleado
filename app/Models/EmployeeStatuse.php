<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeStatuse extends Model
{
    use SoftDeletes;

    protected $table = 'employee_statuses';

    protected $fillable = [
        'status',
        'content',
        'date',
        'values',
        'reason_id',
        'employee_id',
        'user_id',
        'branch_office_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'values' => 'array',
    ];

    
}