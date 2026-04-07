<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Assistence extends Model
{
    //
    protected $table = 'assistances';

    public static function index($filtros = []){
        $filtro_planta       =  $filtros['planta'] ?? 19;
        $filtro_departamento = $filtros['departamento'] ?? [];
        $ids_empleados       = $filtros['empleados'];
        $fecha_inicio = $filtros['fecha_inicio'] ?? null;
        $fecha_fin    = $filtros['fecha_fin'] ?? null;

        $where     = [];
        $bindings  = [];


        if ($fecha_inicio && $fecha_fin) {
            $where[] = 'assistances.date BETWEEN ? AND ?';
            $bindings[] = $fecha_inicio;
            $bindings[] = $fecha_fin;
        }


        if (!empty($filtro_planta)) {
            $where[]   = 'employees.branch_office_id = ?';
            $bindings[] = (int) $filtro_planta;
        }

        if (!empty($filtro_departamento) && is_array($filtro_departamento)) {
            $placeholders = implode(',', array_fill(0, count($filtro_departamento), '?'));
            $where[] = "employees.department_id IN ($placeholders)";
            $bindings = array_merge($bindings, array_map('intval', $filtro_departamento));
        }

        if (!empty($ids_empleados) && is_array($ids_empleados)) {
            $placeholders = implode(',', array_fill(0, count($ids_empleados), '?'));
            $where[] = "assistances.employee_id IN ($placeholders)";
            $bindings = array_merge($bindings, array_map('intval', $ids_empleados));
        }


        $whereSql = !empty($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';
        $sql = "
            SELECT
                assistances.date,
                assistances.week_day,
                assistances.employee_id,
                shift_roles.name AS turno,
                employees.profile_photo_path AS foto,
                employees.full_name AS nombre_empleado,
                employees.id AS clave_empleado,
                hikcentral.device_name AS dispositivo,
                hikcentral.access_time
            FROM assistances
            INNER JOIN employees
                ON assistances.employee_id = employees.id
            LEFT JOIN shift_roles
                ON assistances.shift_role_id = shift_roles.id
            INNER JOIN hikcentral
                ON assistances.employee_id = hikcentral.employee_id
            AND assistances.date = hikcentral.access_date
            $whereSql
            ORDER BY assistances.date DESC, hikcentral.access_time ASC
        ";

        return DB::select($sql, $bindings);
    }



}
