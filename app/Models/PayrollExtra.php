<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollExtra extends Model
{
    use SoftDeletes;
    protected $table = 'payroll_extras';

    protected $fillable = [
        "id",
        "payroll_complement",
        "shift_repositioning",
        "vacation_payment",
        "compensation",
        "holiday_day",
        "double_hours",
        "triple_hours",
        "sunday_premium",
        "debits",
        "document",
        "year",
        "week",
        "employee_id",
        "branch_office_id",
    ];
}
