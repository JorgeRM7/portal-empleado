<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;


class EmployeeEfficiency extends Model
{
    use SoftDeletes;
    protected $table = 'employee_efficiencies_v2';

    protected $fillable = [
        'employee_id',
        'department_id',
        'position_id',
        'branch_office_id',
        'efficiency',
        'amount',
        'month',
        'year',
        'employee_parent_id',
        'entry_date',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;
    protected $dates = ['deleted_at'];

    public static function calculateAmount($efficiency)
    {
        if ($efficiency >= 0 && $efficiency <= 49) return 800;
        if ($efficiency >= 50 && $efficiency <= 79) return 850;
        if ($efficiency >= 80 && $efficiency <= 100) return 900;
        return 0;
    }

    public static function resolveParentId($parent)
    {
        if (!$parent) return null;

        $parentStr = trim((string)$parent);
        $first = trim(explode(',', $parentStr)[0]);

        if (is_numeric($first)) return (int)$first;

        $parentEmployee = \App\Models\Employee::where('full_name', 'LIKE', $first)->first();
        return $parentEmployee ? $parentEmployee->id : null;

    }

    public static function entryDate($employee)
    {
        $entry = $employee->entry_date;
        if ($employee->reentry_date && $employee->reentry_date > $entry) {
            $entry = $employee->reentry_date;
        }
        return $entry;
    }

    public static function index_filter($filtros = [])
    {
        $branchOfficeIds = $filtros['branch_office_id'] ?? [];
        $departmentId    = $filtros['department_id'] ?? [];
        $positionId      = $filtros['position_id'] ?? null;
        $estatus         = $filtros['estatus'] ?? null;
        $employeeIds     = $filtros['employee_ids'] ?? [];
        $month           = $filtros['month'] ?? null;

        $sql = "SELECT
                    ee.id,
                    ee.employee_id,
                    ee.department_id,
                    ee.position_id,
                    ee.entry_date,
                    ee.efficiency,
                    e.employee_parent_id AS jefes_reales_empleado,
                    e.full_name,
                    d.name AS department_name,
                    p.name AS position_name,
                    GROUP_CONCAT(e2.full_name SEPARATOR ', ') AS parent_full_names,
                    ee.branch_office_id,
                    ee.amount,
                    ee.month,
                    ee.year,
                    ee.updated_at,
                    ee.created_at,
                    e.employee_parent_id AS jefe_ids
                FROM employee_efficiencies_v2 AS ee
                LEFT JOIN employees AS e ON ee.employee_id = e.id
                LEFT JOIN departments d ON ee.department_id = d.id
                LEFT JOIN positions p ON ee.position_id = p.id
                LEFT JOIN employees e2 ON FIND_IN_SET(e2.id, REPLACE(e.employee_parent_id, ' ', '')) > 0
                WHERE ee.deleted_at IS NULL
                ";

        $condiciones = [];

        // Empleados
        if (!empty($employeeIds)) {
            $ids = implode(",", array_map('intval', (array)$employeeIds));
            $condiciones[] = "ee.employee_id IN ($ids)";
        }

        // Planta
        if (!empty($branchOfficeIds)) {
            $ids = implode(",", array_map('intval', (array)$branchOfficeIds));
            $condiciones[] = "ee.branch_office_id IN ($ids)";
        }

        // Departamento
        if (!empty($departmentId)) {
            $ids = implode(",", array_map('intval', (array)$departmentId));
            $condiciones[] = "ee.department_id IN ($ids)";
        }

        // Puesto
        if (!empty($positionId)) {
            $condiciones[] = "ee.position_id = " . intval($positionId);
        }

        // Estatus
        if (!empty($estatus)) {
            if ($estatus === 'pending') {
                $condiciones[] = "ee.efficiency IS NULL";
            } elseif ($estatus === 'filled') {
                $condiciones[] = "ee.efficiency IS NOT NULL";
            }
        }

        // Mes y Año
        if (!empty($month)) {
            list($anio, $mes) = explode('-', $month);
            $condiciones[] = "ee.month = " . intval($mes) . " AND ee.year = " . intval($anio);
        }

        // Agregar condiciones
        if (!empty($condiciones)) {
            $sql .= " AND " . implode(" AND ", $condiciones);
        }

        $sql .= " GROUP BY ee.id";

        return DB::select($sql);
    }

    public static function empleados_sodexo($numero_nomina, $nombre)
    {
        $numero_nomina = (int) $numero_nomina;

        $nombre = addslashes($nombre);

        $sql = "
            SELECT
                e.*
            FROM
                employees e
            LEFT JOIN
                benefit_employee be ON be.employee_id = e.id
            WHERE
                (
                    FIND_IN_SET($numero_nomina, REPLACE(e.employee_parent_id, ' ', ''))
                    OR FIND_IN_SET('$nombre', e.employee_parent_id)
                )
                AND e.deleted_at IS NULL
                AND e.status != 'termination'
                AND be.benefit_id = 2
            GROUP BY
                e.id
        ";

        return DB::select($sql);
    }
}
