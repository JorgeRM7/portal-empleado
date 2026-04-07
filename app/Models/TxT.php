<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class TxT extends Model
{
    use SoftDeletes;
    protected $table = 'employee_time_by_time';
    protected $fillable = [
        'date',
        'comment',
        'employee_id',
        'approved_by',
        'declined_by',
        'week_number',
        'week_year',
        'branch_office_id',
        'approved_at',
        'declined_at',
        'schedule_id',
        'schedule_entry_time',
        'schedule_leave_time',
        'hours',
        'validated_at',
        'moment',
        'file_path',
        'employee_incidence_id'

    ];

    public static function index_data($branch_office_id, $employee_id, $department_id, $status, $week, $year, $eliminated)
    {
        $query = "SELECT
                            eo.id,
                            e.full_name AS employee_name,
                            e.department_id,
                            (SELECT name FROM departments WHERE  id = e.department_id) AS area,
                            eo.employee_id,
                            eo.date,
                            s.name AS schedule_name,
                            eo.schedule_id,
                            eo.hours,
                            eo.approved_at,
                            eo.approved_by,
                            u.name AS approved_by_name,
                            eo.validated_at,
                            eo.declined_at,
                            eo.declined_by,
                            ud.name AS declined_by_name,
                            eo.branch_office_id,
                            eo.comment,
                            eo.week_number,
                            eo.week_year,
                            eo.schedule_entry_time,
                            eo.schedule_leave_time,
                            eo.file_path,
                            eo.deleted_at
                        FROM
                            `employee_time_by_time` AS eo
                        LEFT JOIN
                            employees e ON eo.employee_id = e.id
                        LEFT JOIN
                            users u ON eo.approved_by = u.id
                        LEFT JOIN
                            users ud ON eo.declined_by = ud.id
                        LEFT JOIN
                            schedules s ON eo.schedule_id = s.id";

        if ($branch_office_id) {
            $query .= " WHERE eo.branch_office_id = $branch_office_id AND eo.hours > 0";
        }
        if ($department_id) {
            $query .= " AND e.department_id = $department_id";
        }
        if ($employee_id) {
            $query .= " AND eo.employee_id = $employee_id";
        }
        if ($status == 'pendiente') {
            $query .= " AND eo.approved_at IS NULL AND eo.declined_at IS NULL";
        }else if($status == 'aprobado'){
            $query .= " AND eo.approved_at IS NOT NULL AND eo.declined_at IS NULL";
        }else if($status == 'rechazado'){
            $query .= " AND eo.declined_at IS NOT NULL";
        }else if($status == 'validado'){
            $query .= " AND eo.validated_at IS NOT NULL AND eo.approved_at IS NULL";
        }
        if ($week) {
            $query .= " AND eo.week_number = $week";
        }
        if ($year) {
            $query .= " AND eo.week_year = $year";
        }
        if ($eliminated == 'false' || $eliminated == null) {
            $query .= " AND eo.deleted_at IS NULL";
        }else{
            $query .= " AND eo.deleted_at IS NOT NULL";
        }

        $query .= " ORDER BY eo.id DESC";

        return DB::select($query);
    }

    public static function search_employee_data($date, $employee_id){
        $sql = "SELECT
                    assistances.entrance_time as entradaReal,
                    assistances.leave_time as salidaReal,
                    schedules.entry_time as entradaTeorica,
                    schedules.leave_time as salidaTeorica,
                    schedules.name as turno,
                    schedules.id as schedule_id
                FROM assistances
                INNER JOIN schedules ON schedules.id = assistances.schedule_id
                WHERE assistances.date = '$date' AND assistances.employee_id = $employee_id";

        return DB::select($sql);
    }

    public static function index_data_history($branch_office_id, $employee_id, $department_id, $week, $year, $eliminated)
    {
        $query = "SELECT
                            eo.id,
                            e.full_name AS employee_name,
                            e.department_id,
                            (SELECT name FROM departments WHERE  id = e.department_id) AS area,
                            eo.employee_id,
                            eo.date,
                            s.name AS schedule_name,
                            eo.schedule_id,
                            eo.hours,
                            eo.approved_at,
                            eo.approved_by,
                            u.name AS approved_by_name,
                            eo.validated_at,
                            eo.declined_at,
                            eo.declined_by,
                            ud.name AS declined_by_name,
                            eo.branch_office_id,
                            eo.comment,
                            eo.week_number,
                            eo.week_year,
                            eo.schedule_entry_time,
                            eo.schedule_leave_time,
                            eo.file_path,
                            eo.deleted_at
                        FROM
                            `employee_time_by_time` AS eo
                        LEFT JOIN
                            employees e ON eo.employee_id = e.id
                        LEFT JOIN
                            users u ON eo.approved_by = u.id
                        LEFT JOIN
                            users ud ON eo.declined_by = ud.id
                        LEFT JOIN
                            schedules s ON eo.schedule_id = s.id
                        WHERE e.status != 'termination' ";

        if ($branch_office_id) {
            $query .= " AND eo.branch_office_id = $branch_office_id";
        }
        if ($department_id) {
            $query .= " AND e.department_id = $department_id";
        }
        if ($employee_id) {
            $query .= " AND eo.employee_id = $employee_id";
        }

        if ($week) {
            $query .= " AND eo.week_number = $week";
        }
        if ($year) {
            $query .= " AND eo.week_year = $year";
        }
        if ($eliminated == 'false' || $eliminated == null) {
            $query .= " AND eo.deleted_at IS NULL AND (eo.approved_at IS NOT NULL OR eo.hours < 0)";
        }else{
            $query .= "";
        }

        $query .= " ORDER BY eo.id DESC";

        return DB::select($query);
    }

    public static function excelHistory($branch_office_id){
        $sql = "SELECT
            employee_time_by_time.employee_id,
            employees.full_name AS employee,
            SUM(CASE WHEN employee_time_by_time.hours > 0 THEN employee_time_by_time.hours ELSE 0 END) AS horas_positivas,
            SUM(CASE WHEN employee_time_by_time.hours < 0 THEN ABS(employee_time_by_time.hours) ELSE 0 END) AS horas_negativas,
            SUM(CASE WHEN employee_time_by_time.hours > 0 THEN employee_time_by_time.hours ELSE 0 END)
            - SUM(CASE WHEN employee_time_by_time.hours < 0 THEN ABS(employee_time_by_time.hours) ELSE 0 END) AS resultado
        FROM employee_time_by_time
        INNER JOIN employees ON employees.id  = employee_time_by_time.employee_id
        WHERE employee_time_by_time.branch_office_id = $branch_office_id AND employee_time_by_time.approved_at  IS NOT NULL AND employee_time_by_time.deleted_at IS NULL
        GROUP BY employee_time_by_time.employee_id";

        return DB::select($sql);
    }

}
