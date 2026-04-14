<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeComplainsAsigments extends Model
{
    use SoftDeletes;
    protected $table = 'employee_complains_assigment';

    protected $fillable = [
        'employee_complain_id',
        'user_id',
        'assigment_date',
        'assigment_hour',
        'type'
    ];
}
