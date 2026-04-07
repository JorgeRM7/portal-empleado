<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeShiftRole extends Model
{
    use SoftDeletes;
    protected $table = 'employee_shift_roles';
    protected $fillable = [
        'start_date',
        'end_date',
        'employee_id',
        'shift_role_id',
        'next_shift_role_id',
        'active',
        'branch_office_id'
    ];

    public static function getEmployeeShiftRoles($employee_id, $eliminated, $branch_office, $status)
    {
        $query = "SELECT 
                esr.id,
                esr.employee_id,
                esr.shift_role_id,
                esr.next_shift_role_id,
                esr.start_date,
                esr.end_date,
                esr.active,
                esr.branch_office_id,
                e.full_name,
                sr.name as shift_role_name,
                sr2.name as next_shift_role_name,
                esr.deleted_at
            FROM employee_shift_roles esr 
            INNER JOIN employees e ON esr.employee_id = e.id 
                AND e.status != 'termination'
            LEFT JOIN shift_roles sr ON esr.shift_role_id = sr.id
            LEFT JOIN shift_roles sr2 ON esr.next_shift_role_id = sr2.id
            WHERE 1=1
        ";

        $bindings = [];

        // Filtro por employee_id
        if ($employee_id) {
            $query .= " AND esr.employee_id = ?";
            $bindings[] = $employee_id;
        }

        // Filtro por active (más eficiente con CASE o condiciones directas)
        if ($status === 'active') {
            $query .= " AND esr.active = 1";
        } elseif ($status === 'inactive') {
            $query .= " AND esr.active = 0";
        }

        // Filtro por deleted_at
        if ($eliminated === 'true') {
            $query .= " AND esr.deleted_at IS NOT NULL";
        } else {
            $query .= " AND esr.deleted_at IS NULL";
        }

        // Filtro por branch_office
        if ($branch_office) {
            $query .= " AND e.branch_office_id = ?";
            $bindings[] = $branch_office;
        }

        // Optimizar ORDER BY (ordenar por esr.id en lugar de e.id)
        $query .= " ORDER BY esr.employee_id ASC, esr.id ASC";

        return DB::select($query, $bindings);
    }
}
