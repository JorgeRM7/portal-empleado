<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeIncidences extends Model
{

    //use SoftDeletes;
    protected $table = "employee_incidences";

    protected $fillable = [
        "employee_id",
        "incidence_id",
        "validity_from",
        "validity_to",
        "days",
        "week_number",
        "week_year",
        "branch_office_id",
        "approved_by",
        "approved_at",
        "declined_by",
        "declined_at",
        "expires_at",
        "deleted_by",
        "file_path",
        "document_number",
        "schedule_id",
        "before_date",
        "after_date",
        "rest_date",
        "comment",
        "hours_txt",
        "deleted_at"
    ];

    public static function getIncidences($branchOfficeId, $weeknumber, $weekyear, $employeeId, $incidenceId, $eliminated){
        $whereBranchOffice = $branchOfficeId != null ? "AND ei.branch_office_id = $branchOfficeId":"";
        $whereEmployeeId = $employeeId != null ? "AND ei.employee_id = $employeeId":"";
        $whereIncidenceId = $incidenceId != null ? "AND ei.incidence_id = $incidenceId":"";
        $sql = "SELECT 
                    ei.approved_at,
                    ei.declined_at, 
                    udec.name as declined_by,
                    udel.name as deleted_by,
                    ei.before_date, 
                    ei.comment, 
                    ei.created_at, 
                    ei.days,
                    ei.document_number, 
                    ei.employee_id, 
                    ei.expires_at, 
                    ei.file_path, 
                    ei.hours_txt, 
                    ei.id, 
                    ei.incidence_id, 
                    ei.validity_from, 
                    ei.validity_to, 
                    ei.week_number, 
                    ei.week_year, 
                    ei.schedule_id, 
                    ei.before_date, 
                    ei.rest_date,
                    i.name as incidence_name,
                    i.color,
                    e.full_name,
                    u.name as approved_by
                FROM employee_incidences ei
                INNER JOIN incidences i ON ei.incidence_id = i.id
                INNER JOIN employees e ON ei.employee_id = e.id
                LEFT JOIN users u ON ei.approved_by = u.id
                LEFT JOIN users udel ON ei.deleted_by = udel.id
                LEFT JOIN users udec ON ei.declined_by = udec.id
                WHERE ei.deleted_by IS NULL AND ei.deleted_at IS NULL 
                $whereBranchOffice 
                $whereEmployeeId 
                $whereIncidenceId
                ORDER BY ei.created_at DESC";
        return DB::select($sql);
    }

    public static function groupedForIndexByEmployeeId($employeeId)
    {
        $sql = "SELECT
                    e.id AS employee_id,
                    ei.incidence_id AS id,
                    ei.id AS incidence_id,
                    i.name AS type,
                    ei.validity_from AS start_date,
                    CASE 
                        WHEN ei.rest_date IS NOT NULL AND ei.rest_date != '' THEN ei.rest_date
                        ELSE ei.validity_to
                    END AS end_date,
                    ei.comment AS notes,
                    i.color AS type_color
                FROM employees e
                LEFT JOIN employee_incidences ei ON ei.employee_id = e.id
                LEFT JOIN incidences i ON i.id = ei.incidence_id
                WHERE e.id = $employeeId AND deleted_by IS NULL
                ORDER BY e.id, ei.validity_from DESC;
            ";

        $rows = DB::select($sql);

        $sqlTxt = "SELECT
            employee_id,
            SUM(hours) AS total_hours
        FROM employee_time_by_time
        WHERE employee_id = $employeeId AND approved_at IS NOT NULL AND deleted_at IS NULL";

        $txtTotals = collect(DB::select($sqlTxt))
            ->keyBy('employee_id')
            ->map(fn ($r) => (float) ($r->total_hours ?? 0));

        $sqlVacations = "SELECT employee_id, SUM(amount) as total_hours FROM employee_day_vacations WHERE employee_id = $employeeId AND deleted_at IS NULL";
        
        $vacationsTotals = collect(DB::select($sqlVacations))
            ->keyBy('employee_id')
            ->map(fn ($r) => (float) ($r->total_hours ?? 0));

        

        return collect($rows)->groupBy('employee_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => [
                'id'         => $r->id,
                'incidence_id' => $r->incidence_id,
                'type'       => $r->type,
                'start_date' => $r->start_date,
                'end_date'   => $r->end_date,
                'notes'      => $r->notes,
                'type_color' => $r->type_color,
                'total_hours' => $txtTotals[$r->employee_id] ?? 0,
                'vacations' => $vacationsTotals[$r->employee_id] ?? 0,
            ])->values())
            ->toArray();
    }

    public static function getIncidenceById($id_incidence)
    {
        $sql = "SELECT
            e.id,
            e.full_name as empleado,
            bo.name as empresa,
            d.name as departamento,
            p.name as puesto,
            e.entry_date as fecha_ingreso,
            ei.created_at as fecha_solicitud,
            ei.days as dias,
            ei.validity_to as hasta,
            ei.validity_from as desde,
            SUM(edv.amount) as saldo_inicial,
            ei.hours_txt as horas_txt,
            ei.incidence_id as id_incidencia
        FROM employee_incidences ei
            INNER JOIN employees e ON e.id = ei.employee_id
            INNER JOIN branch_offices bo ON bo.id = ei.branch_office_id
            INNER JOIN departments d ON e.department_id = d.id
            INNER JOIN positions p ON p.id = e.position_id
            INNER JOIN employee_day_vacations edv ON e.id = edv.employee_id
        WHERE ei.id = $id_incidence
        GROUP BY e.id";
        return DB::select($sql);
    }

    public static function getIncidenceData($id_incidence)
    {
        $sql = "SELECT
            e.id as employee_id,
            ei.id,
            e.full_name,
            i.name as incidence_name,
            i.id as incidence_id,
            ei.validity_from,
            ei.validity_to,
            ei.days,
            ei.hours_txt,
            ei.comment,
            ei.file_path,
            ei.document_number,
            ei.approved_at,
            ei.declined_at,
            ei.approved_by,
            ei.declined_by,
            s.name as schedule_name,
            ei.rest_date,
            ei.before_date,
            ei.comment,
            i.description,
            u.name
        FROM employee_incidences ei
            INNER JOIN employees e ON e.id = ei.employee_id
            INNER JOIN incidences i ON i.id = ei.incidence_id
            LEFT JOIN schedules s ON s.id = ei.schedule_id
            LEFT JOIN users u ON u.id = ei.approved_by
        WHERE ei.id = $id_incidence
        GROUP BY e.id";
        return DB::select($sql);
    }

    public static function getLastWeekNumber($branch_office_id)
    {
        $sql = "SELECT MIN(week) AS week, MAX(year) AS year from employee_incidences_week_blocked WHERE branch_office_id = $branch_office_id AND estatus = 1";
        return DB::select($sql);
    }
}
