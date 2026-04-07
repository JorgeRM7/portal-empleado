<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryAdjustment extends Model
{
    
    use SoftDeletes;

    protected $table = 'employee_salary_adjustments';

    protected $fillable = [
        'prev_salary',
        'code',
        'adjust_salary',
        'comment',
        'meta',
        'date',
        'employee_id',
        'actual_position_id',
        'actual_department_id',
        'new_position_id',
        'new_department_id',
        'branch_office_id',
        'type_salary_adjustment_movement_id',
        'approved_by',
        'declined_by',
        'reapply_by',
        'approved_at',
        'declined_at',
        'start_training',
        'end_training',
        'days_period',
        'status',
        'reason_rejection',
        'desist_at',
        'week_number',
        'week_year',
        'adjust_salary_approved',
        'approved_daily_sal',
        'approved_net_week',
        'approved_comp',
        'salary_approved_comment',
        'adjust_salary_confirmation',
        'adjust_doc_date',
        'evaluacion_json',
        'evaluacion_puntos',
        'evaluacion_res',
        'fecha_contrato',
        'docs_dir',
        'adjust_base_trial',
    ];

    // index Ajustes salariales
    public static function indexAjustes(
        $branch_office_id,
        $startOfWeek,
        $endOfWeek,
        $department_id,
        $status,
        $employee_id
    ){
        $query = " SELECT 
                esa.id,
                esa.employee_id,
                e.full_name AS employee_name,
                esa.status,
                p2.name AS new_position_name,
                d2.name AS new_department_name,
                esa.start_training,
                esa.end_training,
                esa.evaluacion_res,
                ua.name AS nombre_aprobo,
                esa.approved_at,
                ud.name AS nombre_declino,
                esa.declined_at,
                ur.name AS nombre_reaplico,
                bo.code AS planta,
                esa.deleted_at,
                tsam.id AS tipo_ajuste,
                esa.type_salary_adjustment_movement_id,
                esa.date,
                esa.adjust_salary_confirmation,
                esa.adjust_doc_date,
                esa.desist_at,
                CASE 
                    WHEN esa.adjust_salary_approved IS NOT NULL THEN esa.approved_daily_sal
                    WHEN esa.adjust_base_trial = 1 THEN p2.daily_salary_in_trial
                    ELSE p2.daily_salary
                END AS daily_salary,
                CASE
                    WHEN esa.adjust_salary_approved IS NOT NULL THEN esa.approved_net_week
                    WHEN esa.adjust_base_trial = 1 THEN p2.net_in_trial
                    ELSE p2.net_in_adjust
                END AS net_in_adjust,
                CASE 
                    WHEN esa.adjust_salary_approved IS NOT NULL THEN esa.approved_comp
                    WHEN esa.adjust_base_trial = 1 THEN p2.compensation_in_trial
                    ELSE p2.compensation
                END AS compensation
            FROM 
                employee_salary_adjustments AS esa
            LEFT JOIN 
                employees e ON esa.employee_id = e.id
            LEFT JOIN 
                type_salary_adjustment_movements tsam ON esa.type_salary_adjustment_movement_id = tsam.id
            LEFT JOIN 
                departments d1 ON esa.actual_department_id = d1.id
            LEFT JOIN 
                positions p1 ON esa.actual_position_id = p1.id
            LEFT JOIN 
                departments d2 ON esa.new_department_id = d2.id
            LEFT JOIN 
                positions p2 ON esa.new_position_id = p2.id
            LEFT JOIN 
                users ua ON ua.id = esa.approved_by
            LEFT JOIN 
                users ud ON ud.id = esa.declined_by
            LEFT JOIN 
                users ur ON ur.id = esa.reapply_by
            LEFT JOIN 
                branch_offices bo ON bo.id = esa.branch_office_id
            WHERE 
                esa.deleted_at IS NULL
        ";

        if ($branch_office_id) {
            $query .= " AND esa.branch_office_id = $branch_office_id";
        }
        if ($startOfWeek && $endOfWeek) {
            $query .= " AND esa.start_training BETWEEN '$startOfWeek' AND '$endOfWeek'";
        }
        if (!empty($department_id)) {
            $ids = implode(',', array_map('intval', $department_id));
            $query .= " AND esa.new_department_id IN ($ids)";
        }
        if ($status) {
            $query .= " AND esa.status = $status";
        }
        if ($employee_id) {
            $ids = implode(',', array_map('intval', $employee_id));
            $query .= " AND esa.employee_id IN ($ids)";
        }

        // return response()->json($query);
        return DB::select($query);
    }
    
    // index Semanal
    public static function indexWeekly(
        $branch_office_id,
        $startOfWeek,
        $endOfWeek,
        $department_id,
        $status,
        $employee_id
    ){
        $query = " SELECT 
            esa.id,
            esa.employee_id,
            esa.branch_office_id,
            e.full_name AS employee_name,
            e.status AS estado_empleado,
            d2.name AS new_department_name,
            p2.name AS new_position_name,
            esa.start_training,
            esa.end_training,
            esa.type_salary_adjustment_movement_id,
            esa.approved_at,
            esa.declined_at,
            esa.date,
            esa.week_number,
            esa.week_year,
            esa.new_department_id,
            esa.status,
            esa.adjust_salary_confirmation,
            esa.adjust_doc_date,
            esa.evaluacion_json,
            esa.evaluacion_puntos,
            esa.evaluacion_res,
            esa.fecha_contrato
        FROM 
            employee_salary_adjustments AS esa
        LEFT JOIN 
            employees e ON esa.employee_id = e.id
        LEFT JOIN 
            departments d2 ON esa.new_department_id = d2.id
        LEFT JOIN 
            positions p2 ON esa.new_position_id = p2.id
        WHERE 
            esa.deleted_at IS NULL 
            AND e.deleted_at IS NULL
            AND esa.approved_at IS NULL
            AND esa.declined_at IS NULL
        ";

        if ($branch_office_id) {
            $query .= " AND esa.branch_office_id = $branch_office_id";
        }
        if ($startOfWeek && $endOfWeek) {
            $query .= " AND esa.start_training BETWEEN '$startOfWeek' AND '$endOfWeek'";
        }
        if (!empty($department_id)) {
            $ids = implode(',', array_map('intval', $department_id));
            $query .= " AND esa.new_department_id IN ($ids)";
        }
        if ($status) {
            $query .= " AND esa.status = $status";
        }
        if ($employee_id) {
            $ids = implode(',', array_map('intval', $employee_id));
            $query .= " AND esa.employee_id IN ($ids)";
        }

        // return response()->json($query);
        return DB::select($query);
    }

}
