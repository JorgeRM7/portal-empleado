<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VacationsPerEmployee extends Model
{
    public static function index($filtros = [])
    {
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
            AND edv.deleted_at IS NULL
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

    public static function store($data = []){
        date_default_timezone_set('America/Mexico_City');

        $employee_id = $data['employee_id'] ?? null;
        $amount      = $data['amount'] ?? 0;
        $date        = $data['date'] ?? null;
        $seniority   = $data['seniority'] ?? null;

        if (!$employee_id || !$date) {
            return [
                'success' => false,
                'message' => 'Datos incompletos'
            ];
        }

        $now = date('Y-m-d H:i:s');

        $sqlEmployee = "
            SELECT
                e.branch_office_id,
                e.entry_date
            FROM employees e
            WHERE e.id = '{$employee_id}'
            LIMIT 1
        ";

        $employee = DB::select($sqlEmployee);

        if (empty($employee) || empty($employee[0]->branch_office_id)) {
            return [
                'success' => false,
                'message' => 'No se encontró la sucursal del empleado'
            ];
        }

        $branch_office_id = $employee[0]->branch_office_id;
        $entry_date = $employee[0]->entry_date;

        if ($seniority === null) {
            $fechaIngreso = new \DateTime($entry_date);
            $hoy = new \DateTime();
            $seniority = $hoy->diff($fechaIngreso)->y;
        }

        $sqlInsert = "
            INSERT INTO employee_day_vacations (
                employee_id,
                amount,
                seniority,
                branch_office_id,
                date,
                created_at,
                updated_at
            ) VALUES (
                '{$employee_id}',
                '{$amount}',
                '{$seniority}',
                '{$branch_office_id}',
                '{$date}',
                '{$now}',
                '{$now}'
            )
        ";

        DB::insert($sqlInsert);

        return [
            'success' => true,
            'message' => 'Vacaciones registradas correctamente'
        ];
    }

    public static function findById($id){
        $sql = "
            SELECT *
            FROM employee_day_vacations
            WHERE id = '{$id}'
            LIMIT 1
        ";

        $result = DB::select($sql);

        return $result[0] ?? null;
    }

    public static function updateById($id, $data = []){
        date_default_timezone_set('America/Mexico_City');

        $employee_id = $data['employee_id'];
        $amount      = $data['amount'];
        $seniority   = $data['seniority'];
        $date        = $data['date'];
        $now         = date('Y-m-d H:i:s');

        $sql = "
            UPDATE employee_day_vacations
            SET
                employee_id = '{$employee_id}',
                amount      = '{$amount}',
                seniority   = '{$seniority}',
                date        = '{$date}',
                updated_at  = '{$now}'
            WHERE id = '{$id}'
        ";

        return DB::update($sql);
    }

    public static function softDeleteById($id){
        date_default_timezone_set('America/Mexico_City');
        $now = date('Y-m-d H:i:s');

        $sql = "
            UPDATE employee_day_vacations
            SET
                deleted_at = '{$now}',
                updated_at = '{$now}'
            WHERE id = '{$id}'
        ";

        return DB::update($sql);
    }
}
