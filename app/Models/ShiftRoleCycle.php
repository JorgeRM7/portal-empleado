<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ShiftRoleCycle extends Model
{
    use SoftDeletes;
    protected $table = 'employee_shift_role_cycles';
    protected $fillable = [
        'date',
        'employee_id',
        'schedule_id',
        'shift_role_id',
        'started_at',
        'ends_at'
    ];

    public static function getShiftRoleCycles($employeeId, $endDate, $eliminated, $branch_office)
    {
        $query = "SELECT 
                esrc.id,
                esrc.employee_id,
                esrc.schedule_id,
                esrc.shift_role_id,
                esrc.started_at,
                esrc.ends_at,
                e.full_name, 
                sr.name AS shift_role_name, 
                sch.name AS schedule_name
            FROM employee_shift_role_cycles AS esrc
            INNER JOIN employees AS e ON esrc.employee_id = e.id AND e.status != 'termination'
            INNER JOIN shift_roles AS sr ON esrc.shift_role_id = sr.id
            INNER JOIN schedules AS sch ON esrc.schedule_id = sch.id 
            WHERE esrc.deleted_at IS NULL 
                AND e.branch_office_id = ?
        ";

        $bindings = [$branch_office];

        if ($employeeId) {
            $query .= " AND esrc.employee_id = ?";
            $bindings[] = $employeeId;
        }

        if ($endDate === 'si') {
            $query .= " AND esrc.ends_at IS NULL";
        }

        return DB::select($query, $bindings);
    }

}
