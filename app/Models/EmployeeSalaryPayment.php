<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryPayment extends Model
{
    use SoftDeletes;
    protected $table = 'employee_salary_payments';

    protected $fillable = [
        "id",
        "type",
        "start_date",
        "end_date",
        "week_number",
        "week_year",
        "branch_office_id",
        "credit_number",
        "value",
        "amount",
        "amount_limit",
        "aggregate_amount",
        "amount_credit_monthly",
        "salary_payment_id",
        "employee_id",
        "employee_compensation_id",
    ];
}
