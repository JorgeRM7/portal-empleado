<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PayrollEarningsDeduction extends Model
{
    
    use SoftDeletes;

    protected $table = 'employee_salary_payments';

    protected $fillable = [
        'type',
        'start_date',
        'end_date',
        'week_number',
        'week_year',
        'branch_office_id',
        'credit_number',
        'value',
        'amount',
        'amount_limit',
        'aggregate_amount',
        'amount_credit_monthly',
        'salary_payment_id',
        'employee_id',
        'employee_compensation_id',
    ];

    // index Ajustes salariales
    public static function indexPYD(
        $branch_office_id,
        $employee_id,
        $week_number,
        $week_year,
        $salary_payment_id
    ){
        $query = " SELECT 
                esp.id,
                esp.employee_id,
                sp.type,
                sp.code,
                esp.credit_number,
                sp.apply_piecework,
                sp.apply,
                esp.start_date,
                esp.end_date,
                esp.amount,
                esp.value,
                esp.amount_limit,
                esp.aggregate_amount,
                esp.amount_credit_monthly,
                esp.deleted_at
            FROM employee_salary_payments esp
            INNER JOIN salary_payments sp ON esp.salary_payment_id = sp.id
            WHERE 
                esp.deleted_at IS NULL
        ";

        if ($branch_office_id) {
            $query .= " AND esp.branch_office_id = $branch_office_id";
        }
        if ($employee_id) {
            $ids = implode(',', array_map('intval', $employee_id));
            $query .= " AND esp.employee_id IN ($ids)";
        }
        if ($week_number && $week_year) {
            $query .= " AND esp.week_number = '$week_number' 
                        AND esp.week_year = '$week_year'";
        }
        if ($salary_payment_id) {
            $query .= " AND esp.salary_payment_id = $salary_payment_id";
        }

        $query .= " GROUP BY esp.employee_id, esp.start_date, esp.end_date, esp.salary_payment_id";

        // return response()->json($query);
        return DB::select($query);
    }
}
