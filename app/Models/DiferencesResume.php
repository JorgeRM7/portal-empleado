<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DiferencesResume extends Model
{

    public static function resume_difences($week_year, $planta)
    {
        $where_week = $where_week_year = $where_planta = $where_planta_2 = '';

        if (!empty($week_year)) {
            $tokens = array_map('trim', explode(',', $week_year));
        
            $byYear = [];
            foreach ($tokens as $t) {
                if (strpos($t, '-W') !== false) {
                    list($yy, $wk) = explode('-W', $t);
                    $byYear[$yy][] = (int)ltrim($wk, '0');
                }
            }
        
            $clauses = [];
            foreach ($byYear as $yy => $weeks) {
                if (!empty($weeks)) {
                    $clauses[] = "(eo.week_year = " . (int)$yy .
                                 " AND eo.week_number IN (" . implode(',', $weeks) . "))";
                }
            }
            if (!empty($clauses)) {
                $where_week = " AND (" . implode(' OR ', $clauses) . ")";
            }
        
            $parts = array_map('trim', explode(',', $week_year));
            $parts = array_filter($parts); // quita vacíos
            if (!empty($parts)) {
                $quoted = implode(', ', array_map(function($w){
                    return "'" . addslashes($w) . "'";
                }, $parts));
                $where_week_year = " AND eoe.week IN ($quoted)";
            }
        }
        
        if (!empty($planta)) {
            $planta = (int)$planta;
            $where_planta   = " AND eoe.branch_office_id = $planta";
            $where_planta_2 = " AND (CASE
                                WHEN eo.pay_external = 1 THEN eo.external_branch_office_id
                                ELSE eo.branch_office_id
                            END) = $planta";
        }
        
        $sql = "SELECT
                p.id                           AS idpuesto,
                p.name                         AS name,
                p.daily_salary                 AS daily_salary,
                x.week_key                     AS week,
                COALESCE(SUM(x.overtime_estimate), 0)            AS total_overtime_estimate,
                COALESCE(SUM(x.double_overtime_estimate), 0)     AS total_double_overtime_estimate,
                COALESCE(SUM(x.triple_overtime_estimate), 0)     AS total_triple_overtime_estimate,
                COALESCE(SUM(x.overtimes), 0)                    AS total_overtimes,
                COALESCE(SUM(x.double_overtimes_extra), 0)       AS double_overtimes_extra,
                COALESCE(SUM(x.triple_overtimes), 0)             AS triple_overtimes,
                MAX(x.salario_diario)                            AS salario_diario
            FROM positions p
            LEFT JOIN (
                SELECT
                    eoe.position_id                               AS position_id,
                    eoe.week                                      AS week_key,
                    SUM(eoe.overtime)                             AS overtime_estimate,
                    SUM(eoe.double_overtime)                      AS double_overtime_estimate,
                    SUM(eoe.triple_overtime)                      AS triple_overtime_estimate,
                    0                                             AS overtimes,
                    0                                             AS double_overtimes_extra,
                    0                                             AS triple_overtimes,
                    NULL                                          AS salario_diario
                FROM employee_overtimes_estimate eoe
                WHERE eoe.approved_at IS NOT NULL
                      $where_week_year
                      $where_planta
                GROUP BY eoe.position_id, eoe.week
            
                UNION ALL
            
                SELECT
                    emp.position_id                                AS position_id,
                    CASE
                      WHEN eo.week_year IS NULL OR eo.week_number IS NULL THEN NULL
                      ELSE CONCAT(eo.week_year, '-W', LPAD(eo.week_number, 2, '0'))
                    END                                            AS week_key,
                    0                                              AS overtime_estimate,
                    0                                              AS double_overtime_estimate,
                    0                                              AS triple_overtime_estimate,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(eo.overtimes, '$.total'))           AS DECIMAL(18,4))) AS overtimes,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(eo.overtimes, '$.double_overtime')) AS DECIMAL(18,4))) AS double_overtimes_extra,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(eo.overtimes, '$.triple_overtime')) AS DECIMAL(18,4))) AS triple_overtimes,
                    MAX(pd.daily_salary)                            AS salario_diario
                FROM employee_overtimes eo
                INNER JOIN employees emp ON emp.id = eo.employee_id
                LEFT JOIN payment_data pd ON emp.id = pd.owner_id
                WHERE eo.approved_at IS NOT NULL
                      AND eo.deleted_at IS NULL
                      $where_week
                      $where_planta_2
                GROUP BY emp.position_id, eo.week_year, eo.week_number
            ) x ON x.position_id = p.id
            GROUP BY
                p.id, p.name, p.daily_salary, x.week_key
            HAVING
                   COALESCE(SUM(x.overtime_estimate), 0) > 0
                OR COALESCE(SUM(x.double_overtime_estimate), 0) > 0
                OR COALESCE(SUM(x.triple_overtime_estimate), 0) > 0
                OR COALESCE(SUM(x.overtimes), 0) > 0
            ORDER BY
                p.name, x.week_key
        ";

        $result = DB::select($sql);

        return $result;
    }

    public static function get_detalles_resumen($id, $semana_anio){
        
        $parts = array_map('trim', explode(',', $semana_anio));
            
        $quoted = implode(', ', array_map(function($w){
          return "'" . addslashes($w) . "'";
        }, $parts));
        
        $where = " AND eoe.week IN ($quoted)";
        
        
        $sql = "SELECT
                    eoe.overtime,
                    eoe.double_overtime,
                    eoe.triple_overtime,
                    CASE
                        WHEN m.id = 38 THEN eoe.coment
                        ELSE m.description
                      END AS description,
                    m.name as motivo,
                    p.name as puesto,
                    p.daily_salary as salariodiario
                FROM employee_overtimes_estimate eoe
                INNER JOIN positions p ON p.id = eoe.position_id
                INNER JOIN motivos m ON m.id = eoe.motivo
                WHERE eoe.position_id = $id $where";
        // dd($sql);
       return DB::select($sql);
    }

    public static function get_detalles_empleados($semana_anio, $id){
        
        $tokens = array_map('trim', explode(',', $semana_anio));

        $byYear = [];
        foreach ($tokens as $t) {
            list($yy, $wk) = explode('-W', $t);
            $byYear[$yy][] = (int)ltrim($wk, '0');
        }
        
        $clauses = [];
        foreach ($byYear as $yy => $weeks) {
            $clauses[] = "(eo.week_year = " . (int)$yy .
                         " AND eo.week_number IN (" . implode(',', $weeks) . "))";
        }
        
        $where = " AND (" . implode(' OR ', $clauses) . ")";
        
        $sql = "SELECT
                    JSON_VALUE(eo.overtimes, '$.total') AS overtimes,
                    JSON_VALUE(eo.overtimes, '$.double_overtime') AS double_overtimes_extra,
                    JSON_VALUE(eo.overtimes, '$.triple_overtime') AS triple_overtimes,
                    p.name,
                    CASE
                        WHEN m.id = 109 THEN eo.comment
                        ELSE m.description
                      END AS description,
                    m.name AS nombremotivo,
                    p.daily_salary as salariodiario
                FROM employee_overtimes eo
                    INNER JOIN employees e ON e.id = eo.employee_id
                    INNER JOIN positions p ON p.id = e.position_id
                    INNER JOIN motivos m ON m.id = eo.motivo
                WHERE p.id = $id AND eo.approved_at IS NOT NULL $where";
        return DB::select($sql);
    }
}
