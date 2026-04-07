<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PayrollTypes extends Model
{
    use SoftDeletes;

    protected $table = "payroll_types";

    protected $fillable = [
        "name",
        "static_fields",
        "debit_fields",
        "credit_fields",
        "active",
        "requires_date",
        "apply_departments",
    ];

    public static function getPayrollKeys(){
        $keys = DB::select('SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                    WHERE TABLE_NAME = "payroll_department_items" 
                    AND TABLE_SCHEMA = "u968951568_employee_local" 
                    AND COLUMN_NAME NOT IN ("active", "created_at", "updated_at", "deleted_at", "id", "clave", "payroll_department_id")');
        return $keys;
    }

    public static function getPayrollBranchOffices($id){
        $branch = DB::select('SELECT branch_offices.code, branch_offices.id FROM branch_office_payroll_type
                                INNER JOIN branch_offices ON branch_office_payroll_type.branch_office_id = branch_offices.id 
                                WHERE payroll_type_id = ' . $id . '');
        return $branch;
    }
}
