<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeesParents extends Model
{
    use SoftDeletes;
    protected $table = 'employees_parents';

    protected $fillable = [
        'employee_id',
        'parent_id'
    ];
}
