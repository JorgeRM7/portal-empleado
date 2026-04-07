<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeSalaryAdjustmentMovement extends Model
{
    
    use SoftDeletes;

    protected $table = 'type_salary_adjustment_movements';
}
