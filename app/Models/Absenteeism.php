<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Absenteeism extends Model
{
    //
    // protected $table = 'weekly_assistances';

    public static function index($filtros = []){
        $where = [];


        if (!empty($filtros['semana'])) {
            $week = $filtros['semana'];
            $year = $filtros['anio'];
            $where[] = "assistances.week_number = $week AND assistances.week_year = $year";
        }

        if (!empty($filtros['departamento'])) {
            $ids = implode(",", $filtros['departamento']);
            $where[] = "employees.department_id IN ($ids)";
        }

        if (!empty($filtros['planta'])) {
            $where[] = "employees.branch_office_id = {$filtros['planta']}";
        }

        if (!empty($filtros['empleados'])) {
            $ids = implode(",", $filtros['empleados']);
            $where[] = "assistances.employee_id IN ($ids)";
        }

        if (!empty($filtros['tipo_falta'])) {
            $where[] = "incidences.category_incidence_id = {$filtros['tipo_falta']}";
        }

        $where[] = "assistances.incidence_id IN (9,10,11,50,4,5,6,7,8,16,50,67)";
        $where[] = "employees.status <> 'termination'";
        $where[] = "assistances.deleted_at IS NULL";

        $whereSql = count($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        $sql = "
            SELECT 
                employees.id AS employee_id,
                incidences.external_code,
                employee_incidences.document_number,
                incidences.incapacity_code,
                assistances.date
            FROM assistances 
            INNER JOIN employees ON employees.id = assistances.employee_id 
            LEFT JOIN employee_incidences ON employee_incidences.id  = assistances.employee_incidence_id
            INNER JOIN incidences ON incidences.id = assistances.incidence_id  
            $whereSql
        ";

        return DB::select($sql);
    }


}
