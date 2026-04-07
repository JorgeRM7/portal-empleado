<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeVacation extends Model
{
    protected $table = 'employee_vacations';
    public $timestamps = false;

    /**
     * Vacaciones
     */
    public static function index_vacaciones($filtros = []) {

        $filtro_semana = $filtros['semana'] ?? null;
        $filtro_planta = $filtros['planta'] ?? null;
        $ids_empleados = $filtros['empleados'] ?? [];
        $filtro_departamento = $filtros['departamento'] ?? [];

        $sql = "
            SELECT
                    ev.employee_id AS clave_empleado,
                    e.full_name AS nombre_empleado,
                    e.branch_office_id AS planta,
                    ev.start_to AS fecha_disfrute,
                    ev.days AS dias_disfruteeeeee,
                    SUM(DATEDIFF(employee_incidences.validity_to, employee_incidences.validity_from) + 1) AS dias_disfrute,
                    ev.deleted_at,
                    TIMESTAMPDIFF(YEAR, e.entry_date, CURDATE()) AS antiguedad,
                    (
                        SELECT
                            (
                                (IFNULL(wa.monday_status, 0) = 3) +
                                (IFNULL(wa.tuesday_status, 0) = 3) +
                                (IFNULL(wa.wednesday_status, 0) = 3) +
                                (IFNULL(wa.thursday_status, 0) = 3) +
                                (IFNULL(wa.friday_status, 0) = 3) +
                                (IFNULL(wa.saturday_status, 0) = 3) +
                                (IFNULL(wa.sunday_status, 0) = 3)
                            )
                        FROM weekly_assistances wa
                        WHERE wa.employee_id = ev.employee_id
                        AND wa.week_number = ev.week_number
                        AND wa.week_year = ev.week_year
                        AND wa.deleted_at IS NULL
                        LIMIT 1
                    ) AS total_vacaciones,
                    ev.week_number,
                    ev.week_year
                FROM employee_vacations ev
                LEFT JOIN employees e ON ev.employee_id = e.id
                LEFT JOIN employee_incidences ON employee_incidences.id = ev.employee_incidence_id
                WHERE 1=1
            ";

        $condiciones = [];

        // Semana
        if (!empty($filtro_semana) && str_contains($filtro_semana, '-W')) {
            [$year, $weekNumber] = explode('-W', $filtro_semana);
            $condiciones[] = "ev.week_number = " . (int)$weekNumber;
            $condiciones[] = "ev.week_year = " . (int)$year;
        }

        //Plantas
        if (!empty($filtro_planta)) {
            $condiciones[] = "e.branch_office_id = " . intval($filtro_planta);
        }

        // Departamento
        if (!empty($filtro_departamento)) {
            $ids = implode(',', array_map('intval', $filtro_departamento));
            $condiciones[] = "e.department_id IN ($ids)";
        }

        // Empleados
        if (!empty($ids_empleados)) {
            $ids = implode(',', array_map('intval', $ids_empleados));
            $condiciones[] = "ev.employee_id IN ($ids)";
        }

        if (!empty($condiciones)) {
            $sql .= " AND " . implode(" AND ", $condiciones);
        }

        $sql .= "
            GROUP BY ev.employee_id, ev.week_number, ev.week_year
            HAVING total_vacaciones > 0
        ";

        return DB::select($sql);
    }

    /**
     * Saldos
     */
    public static function index_saldos($filtros = []) {

        $filtro_planta = $filtros['planta'] ?? null;
        $ids_empleados = $filtros['empleados'] ?? [];
        $filtro_departamento = $filtros['departamento'] ?? [];

        $sql = "
            SELECT
                employee_day_vacations.deleted_at,
                employees.id AS clave_empleado,
                employees.full_name AS nombre_empleado,
                d.name AS departamento,
                COALESCE(employees.reentry_date, employees.entry_date) AS fecha_ingreso,
                TIMESTAMPDIFF(
                    YEAR,
                    COALESCE(employees.reentry_date, employees.entry_date),
                    CURDATE()
                ) AS antiguedad,
                SUM(CASE
                        WHEN employee_day_vacations.amount > 0
                        THEN employee_day_vacations.amount
                        ELSE 0
                    END) AS correspondientes,
                ABS(SUM(CASE
                        WHEN employee_day_vacations.amount < 0
                        THEN employee_day_vacations.amount
                        ELSE 0
                    END)) AS disfrutados,
                SUM(employee_day_vacations.amount) AS disponibles
            FROM employee_day_vacations
            INNER JOIN employees
                ON employees.id = employee_day_vacations.employee_id
            LEFT JOIN departments d
                ON d.id = employees.department_id
            WHERE (
                    (employees.reentry_date IS NOT NULL
                    AND employee_day_vacations.date >= employees.reentry_date)
                OR (employees.reentry_date IS NULL
                    AND employee_day_vacations.date >= employees.entry_date)
            )
        ";

        $condiciones[] = "employee_day_vacations.deleted_at IS NULL";

        // Planta
        if (!empty($filtro_planta)) {
            $condiciones[] = "employees.branch_office_id = " . intval($filtro_planta);
        }

        // Departamento
        if (!empty($filtro_departamento)) {
            $ids = implode(',', array_map('intval', $filtro_departamento));
            $condiciones[] = "employees.department_id IN ($ids)";
        }

        // Empleados
        if (!empty($ids_empleados)) {
            $ids = implode(',', array_map('intval', $ids_empleados));
            $condiciones[] = "employee_day_vacations.employee_id IN ($ids)";
        }

        if (!empty($condiciones)) {
            $sql .= ' AND ' . implode(' AND ', $condiciones);
        }

        $sql .= "
            GROUP BY employees.id, employees.full_name, d.name, employees.entry_date, employees.reentry_date
            ORDER BY employees.full_name ASC
        ";

        return DB::select($sql);
    }

    /**
     * Movimientos
     */
    public static function index_movimientos($filtros = []) {

        $filtro_planta = $filtros['planta'] ?? null;
        $ids_empleados = $filtros['empleados'] ?? [];

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
            AND edv.deleted_at is null
        ";

        $condiciones = [];

        if (!empty($ids_empleados)) {
            $ids = implode(',', array_map('intval', $ids_empleados));
            $condiciones[] = "edv.employee_id IN ($ids)";
        }

        if (!empty($filtro_planta)) {
            $plantas = implode(',', array_map('intval', (array)$filtro_planta));
            $condiciones[] = "edv.branch_office_id IN ($plantas)";
        }

        if (!empty($condiciones)) {
            $sql .= ' AND ' . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY e.full_name ASC";

        return DB::select($sql);


    }
}
