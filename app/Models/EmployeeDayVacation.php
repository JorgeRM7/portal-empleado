<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDayVacation extends Model
{
    use SoftDeletes;
    protected $table = 'employee_day_vacations';
    protected $fillable = [
        'employee_id',
        'amount',
        'branch_office_id',
        'employee_incidence_id',
        'date',
        'seniority',
    ];
}
