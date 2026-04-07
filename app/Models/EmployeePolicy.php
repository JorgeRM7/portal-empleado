<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePolicy extends Model
{
    use SoftDeletes;

    protected $table = 'employee_policies';

    protected $fillable = [
        'name',
        'vacations',
        'week_work_days',
        'vacation_bonus_year',
        'absences_discount',
        'incidences_discount',
        'vacation_bonus',
        'payment_days', // siempre 0
        'seventh_day_discount', // siempre 0
        // 'christmas_bonus',
        // 'profits',
    ];

    protected $casts = [
        'vacations'            => 'array',
        'vacation_bonus_year'  => 'boolean',
        'absences_discount'    => 'boolean',
        'incidences_discount'  => 'boolean',
    ];

}
