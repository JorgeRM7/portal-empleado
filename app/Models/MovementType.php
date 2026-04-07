<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementType extends Model
{
    use SoftDeletes;
    
    protected $table = 'type_salary_adjustment_movements';

    protected $fillable = [
        'name',
        'branch_office_id',
    ];
}
