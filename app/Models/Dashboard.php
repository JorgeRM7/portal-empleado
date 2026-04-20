<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dashboard extends Model
{
    public static function getData($id)
    {
        $employee = DB::selectOne("
            SELECT
                employees.id,
                employees.email,
                employees.status,
                employees.dni,
                employees.profile_photo_path,
                branch_offices.code AS planta,
                employees.entry_date,
                employees.full_name,
                employees.birthday,
                users.username,
                positions.name AS posicion,
                employees.personal_phone AS telefono,
                employees.name,
                employees.surname,
                employees.mother_surname,
                employees.terms_condition,
                departments.name AS departamento,
                JSON_UNQUOTE(JSON_EXTRACT(employees.additional_info, '$.profession')) AS level_study
            FROM employees
            INNER JOIN genders ON genders.id = employees.gender_id
            INNER JOIN branch_offices ON branch_offices.id = employees.branch_office_id
            INNER JOIN users ON users.id = employees.user_id
            INNER JOIN positions ON positions.id = employees.position_id
            INNER JOIN departments ON departments.id = employees.department_id
            WHERE employees.id = ?
        ", [$id]);

        $vacaciones = DB::selectOne("SELECT SUM(amount) AS vacaciones_disponibles FROM `employee_day_vacations` WHERE employee_id =?  AND deleted_at is null" ,[$id]);

        $incidencias_empleado = DB::selectOne("SELECT COUNT(id) AS incidencias_empleado FROM `employee_incidences` WHERE employee_id =? AND expires_at IS NULL", [$id]);


        $asistencia = DB::select("
            SELECT
            	(SELECT code FROM incidences WHERE id = weekly_assistances.monday_status) AS lunes,
                (SELECT code FROM incidences WHERE id = weekly_assistances.tuesday_status) AS martes,
                (SELECT code FROM incidences WHERE id = weekly_assistances.wednesday_status) AS miercoles,
                (SELECT code FROM incidences WHERE id = weekly_assistances.thursday_status) AS jueves,
                (SELECT code FROM incidences WHERE id = weekly_assistances.friday_status) AS viernes,
                (SELECT code FROM incidences WHERE id = weekly_assistances.saturday_status) AS sabado,
                (SELECT code FROM incidences WHERE id = weekly_assistances.sunday_status) AS domingo,

                (SELECT color FROM incidences WHERE id = weekly_assistances.monday_status) AS color_lunes,
                (SELECT color FROM incidences WHERE id = weekly_assistances.tuesday_status) AS color_martes,
                (SELECT color FROM incidences WHERE id = weekly_assistances.wednesday_status) AS color_miercoles,
                (SELECT color FROM incidences WHERE id = weekly_assistances.thursday_status) AS color_jueves,
                (SELECT color FROM incidences WHERE id = weekly_assistances.friday_status) AS color_viernes,
                (SELECT color FROM incidences WHERE id = weekly_assistances.saturday_status) AS color_sabado,
                (SELECT color FROM incidences WHERE id = weekly_assistances.sunday_status) AS color_domingo,

                (SELECT name FROM incidences WHERE id = weekly_assistances.monday_status) AS nombre_lunes,
                (SELECT name FROM incidences WHERE id = weekly_assistances.tuesday_status) AS nombre_martes,
                (SELECT name FROM incidences WHERE id = weekly_assistances.wednesday_status) AS nombre_miercoles,
                (SELECT name FROM incidences WHERE id = weekly_assistances.thursday_status) AS nombre_jueves,
                (SELECT name FROM incidences WHERE id = weekly_assistances.friday_status) AS nombre_viernes,
                (SELECT name FROM incidences WHERE id = weekly_assistances.saturday_status) AS nombre_sabado,
                (SELECT name FROM incidences WHERE id = weekly_assistances.sunday_status) AS nombre_domingo,

                weekly_assistances.monday_data,
                weekly_assistances.tuesday_data,
                weekly_assistances.wednesday_data,
                weekly_assistances.thursday_data,
                weekly_assistances.friday_data,
                weekly_assistances.saturday_data,
                weekly_assistances.sunday_data,

                weekly_assistances.week_number,
                weekly_assistances.week_year
            FROM `weekly_assistances`
            WHERE weekly_assistances.employee_id = ? AND weekly_assistances.deleted_at IS NULL
            GROUP BY weekly_assistances.week_number, weekly_assistances.week_year
            ORDER BY weekly_assistances.id DESC
        ",[$id]);

        $fechaIngreso = Carbon::parse($employee->entry_date);
        $hoy = Carbon::now();

        $diff = $fechaIngreso->diff($hoy);

        $antiguedad = $diff->y . ' años, ' . $diff->m . ' meses y ' . $diff->d . ' días';

        return [
            'employee'              => $employee,
            'vacaciones'            =>$vacaciones,
            'incidencias_empleado'  => $incidencias_empleado,
            'asistencia'            =>$asistencia,
            'antiguedad'            => $antiguedad
        ];
    }

    public static function dashboardVacaciones($data = [])
    {
        $ids_empleados = $data['empleados'] ?? [];

        $sql = "
            SELECT
                edv.id,
                edv.employee_id,
                TRIM(e.full_name) AS full_name,
                edv.amount,
                edv.seniority,
                edv.date,
                edv.branch_office_id
            FROM employee_day_vacations AS edv
            LEFT JOIN employees AS e ON edv.employee_id = e.id
            WHERE e.deleted_at IS NULL
            AND edv.employee_id IS NOT NULL
            AND e.full_name IS NOT NULL
            AND edv.deleted_at IS NULL
        ";

        if (!empty($ids_empleados)) {
            $ids = implode(',', array_map('intval', $ids_empleados));
            $sql .= " AND edv.employee_id IN ($ids)";
        }

        return DB::select($sql);
    }

    public static function dashboardIncidencias($data = [])
    {
        $ids_empleados = $data['empleados'] ?? [];

        $sql = "
            SELECT
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
        ";

        if (!empty($ids_empleados)) {
            $ids = implode(',', array_map('intval', $ids_empleados));
            $sql .= " AND ei.employee_id IN ($ids)";
        }

        return DB::select($sql);
    }
}
