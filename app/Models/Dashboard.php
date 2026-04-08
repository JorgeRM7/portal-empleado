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
                (SELECT name FROM incidences WHERE id = weekly_assistances.thursday_status) AS nombre_domingo,
                (SELECT name FROM incidences WHERE id = weekly_assistances.friday_status) AS nombre_domingo,
                (SELECT name FROM incidences WHERE id = weekly_assistances.saturday_status) AS nombre_domingo,
                (SELECT name FROM incidences WHERE id = weekly_assistances.sunday_status) AS nombre_domingo,
                
                weekly_assistances.week_number,
                weekly_assistances.week_year
                
            FROM `weekly_assistances`
            WHERE weekly_assistances.employee_id =? AND weekly_assistances.deleted_at IS NULL 
            GROUP BY  weekly_assistances.week_number, weekly_assistances.week_year
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
}
