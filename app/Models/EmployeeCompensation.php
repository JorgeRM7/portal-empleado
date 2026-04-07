<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeCompensation extends Model
{
    use SoftDeletes;
    protected $table = 'employee_compensation';
    protected $fillable = [
        'employee_id',
        'percent',
        'compensation',
        'transport',
        'piece_work',
        'extra_compensation',
        'overtime',
        'total',
        'comment',
        'week_number',
        'week_year',
        'salary_payments',
        'approved_at',
        'approved_by',
        'validated_at',
        'validated_by',
        'declined_at',
        'declined_by',
        'branch_office_id',
        'week_from',
        'week_to',
    ];

    public static function getCompensations($branchOfficeId, $departmentId, $weekNumber, $weekYear, $approved, $employeeId){
        $whereApproved = $approved ? "AND ec.approved_at IS NOT NULL" : "";
        $whereEmployeeId = $employeeId ? "AND ec.employee_id = $employeeId" : "";
        $whereWeekNumber = $weekNumber ? "AND ec.week_number = $weekNumber" : "";
        $whereWeekYear = $weekYear ? "AND ec.week_year = $weekYear" : "";
        $whereDepartmentId = $departmentId ? "AND e.department_id = $departmentId" : "";

        $sql = "SELECT ec.id AS id,
                    e.id AS clave_empleado,
                    e.full_name AS nombre_empleado,
                    ec.compensation AS compensacion,
                    ec.extra_compensation AS extra_compensacion,
                    ec.piece_work,
                    ec.overtime AS tiempo_extra,
                    ec.percent AS eficiencia,
                    ec.transport AS apoyo_transporte,
                    ec.total AS total,
                    ec.comment AS observaciones,
                    ec.approved_at AS fecha_aprobado,
                    ec.declined_at AS fecha_rechazado,
                    p.compensation AS compensacion_puesto,
                    p.name AS posicion,
                    d.name AS departamento,
                    ec.created_at AS creado,
                    ec.updated_at AS actualizado,
                    u.name AS aprobado_por,
                    ec.deleted_at
                FROM employee_compensation ec
                JOIN employees e ON ec.employee_id = e.id
                LEFT JOIN users u ON ec.approved_by = u.id
                JOIN positions p ON e.position_id = p.id
                JOIN departments d ON e.department_id = d.id
                WHERE ec.deleted_at IS NULL 
                AND  ec.branch_office_id = $branchOfficeId
                $whereWeekYear
                $whereWeekNumber
                $whereDepartmentId
                $whereApproved
                $whereEmployeeId
                ";

            // dd($sql);

        return DB::select($sql);
    }
}
