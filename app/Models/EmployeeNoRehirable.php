<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeNoRehirable extends Model
{
    protected $table = 'employees';
    public $timestamps = false;

    public static function index_no_rehirable($filtros = []){
        $filtro_planta      = $filtros['planta'] ?? [];
        $filtro_employees   = $filtros['employees'] ?? [];

        $sql = "
            SELECT
                employees.id,
                employees.id AS clave_empleado,
                branch_offices.name AS branch_office_name,
                employees.full_name AS nombre_empleado,
                DATE_FORMAT(
                    GREATEST(
                        IFNULL(employees.entry_date, '1900-01-01'),
                        IFNULL(employees.reentry_date, '1900-01-01')
                    ),
                    '%d-%m-%Y'
                ) AS entry_date,
                employees.termination_date,
                employees.rehireable,
                employee_status_reasons.name AS termination_reason,
                employees.branch_office_id
            FROM employees
            JOIN employee_statuses
                ON employees.id = employee_statuses.employee_id
            JOIN employee_status_reasons
                ON employee_statuses.reason_id = employee_status_reasons.id
            JOIN branch_offices
                ON employees.branch_office_id = branch_offices.id
            WHERE employee_statuses.status = 'termination'
                AND employee_statuses.deleted_at IS NULL
                AND employees.rehireable = 0
                AND employee_status_reasons.name NOT IN (
                    'ALTAS', 'TRASPASO', 'REINGRESO', 'NUEVO INGRESO'
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM employee_rehireables
                    WHERE employee_rehireables.employee_id = employees.id
                )
        ";

        if (!empty($filtro_planta)) {
            $ids = implode(',', array_map('intval', (array) $filtro_planta));
            $sql .= " AND employees.branch_office_id IN ($ids)";
        }

        if (!empty($filtro_employees)) {
            $ids = implode(',', array_map('intval', (array) $filtro_employees));
            $sql .= " AND employees.id IN ($ids)";
        }

        $sql.= "GROUP BY employees.id";

        return DB::select($sql);
    }


    // public static function export_noRehirable($fechaInicio, $fechaFin) {

    //     $sqlExportNoRehirable = "
    //         SELECT
    //             e.id AS numero_nomina,
    //             e.full_name AS empleado,
    //             e.entry_date AS fecha_ingreso,
    //             e.termination_date AS fecha_terminacion,
    //             e.birthday AS cumpleaños,
    //             g.name AS genero,
    //             bo.name AS razon_social,
    //             d.name AS departamento,
    //             p.name AS posicion,
    //             j.full_name AS jefe_inmediato,
    //             TIMESTAMPDIFF(YEAR, e.entry_date, IFNULL(e.termination_date, CURDATE())) AS años_antiguedad,
    //             TIMESTAMPDIFF(YEAR, e.birthday, CURDATE()) AS edad,
    //             ers.name AS razon_baja,
    //             es.content AS comentario_baja
    //         FROM employees e
    //         LEFT JOIN genders g ON g.id = e.gender_id
    //         LEFT JOIN branch_offices bo ON bo.id = e.branch_office_id
    //         LEFT JOIN departments d ON d.id = e.department_id
    //         LEFT JOIN positions p ON p.id = e.position_id
    //         LEFT JOIN employees j ON j.id = e.employee_parent_id
    //         LEFT JOIN employee_statuses es ON es.employee_id = e.id
    //         LEFT JOIN employee_status_reasons ers ON ers.id = es.reason_id
    //         WHERE e.status = 'termination'
    //         AND e.termination_date IS NOT NULL
    //         AND e.termination_date BETWEEN '{$fechaInicio}' AND '{$fechaFin}'
    //         AND ers.name IN ('SEPARACION VOLUNTARIA', 'BAJA REQUERIDA', 'ABANDONO DE EMPLEO')
    //         GROUP BY e.id";
    //     return DB::select($sqlExportNoRehirable);
    // }

}
