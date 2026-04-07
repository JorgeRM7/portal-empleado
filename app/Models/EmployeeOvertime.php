<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeOvertime extends Model
{
    use SoftDeletes;
    protected $table = 'employee_overtimes';

    protected $fillable = [
        'date',
        'comment',
        'employee_id',
        'approved_by',
        'declined_by',
        'week_number',
        'week_year',
        'branch_office_id',
        'approved_at',
        'declined_at',
        'overtimes',
        'schedule_id',
        'hours',
        'has_error',
        'complete',
        'rest',
        'type',
        'pay_external',
        'external_branch_office_id',
        'untimely',
        'corrido',
        'untimely_date',
        'txt',
        'motivo'

    ];

    public static function search_employee_data($date, $employee_id){
        $sql = "SELECT
                    assistances.entrance_time as entradaReal,
                    assistances.leave_time as salidaReal,
                    schedules.entry_time as entradaTeorica,
                    schedules.leave_time as salidaTeorica,
                    schedules.name as turno,
                    schedules.id as schedule_id
                FROM assistances
                INNER JOIN schedules ON schedules.id = assistances.schedule_id
                WHERE assistances.date = '$date' AND assistances.employee_id = $employee_id";

        return DB::select($sql);
    }

    public static function index( $empleado, $planta, $departamento, $estatus, $semana, $primaDominical){

        if(!empty($planta)){
            $where = "WHERE (CASE
                WHEN employee_overtimes.pay_external = 1 THEN employee_overtimes.external_branch_office_id
                ELSE employee_overtimes.branch_office_id
            END) = $planta AND employee_overtimes.deleted_at IS NULL AND employees.deleted_at IS NULL";
        }else{
            $where = "WHERE employee_overtimes.deleted_at IS NULL AND employees.deleted_at IS NULL";
        }

        if (!empty($empleado)) {
            $where .= " AND employee_overtimes.employee_id = '$empleado'";
        }

        if (!empty($departamento)) {
            $where .= " AND employees.department_id = '$departamento'";
        }

        if (!empty($estatus)) {
            if ($estatus === 'approved') {
                $where .= " AND employee_overtimes.approved_at IS NOT NULL";
            } elseif ($estatus === 'pending') {
                $where .= " AND employee_overtimes.approved_at IS NULL AND employee_overtimes.declined_at IS NULL AND employee_overtimes.txt != 1";
            } elseif ($estatus === 'declined') {
                $where .= " AND employee_overtimes.declined_at IS NOT NULL";
            }elseif ($estatus === 'txt_pendiente') {
                $where .= " AND employee_overtimes.txt = 1 AND employee_overtimes.approved_at IS NULL AND employee_overtimes.declined_at IS NULL";
            }elseif ($estatus === 'txt_aprobado') {
                $where .= " AND employee_overtimes.txt = 1 AND employee_overtimes.approved_at IS NOT NULL";
            }
        }

        if (!empty($semana)) {
            $tokens = array_map('trim', explode(',', $semana));

            $byYear = [];
            foreach ($tokens as $t) {
                list($yy, $wk) = explode('-W', $t);
                $byYear[$yy][] = (int)ltrim($wk, '0');
            }

            $clauses = [];
            foreach ($byYear as $yy => $weeks) {
                $clauses[] = "(employee_overtimes.week_year = " . (int)$yy .
                             " AND employee_overtimes.week_number IN (" . implode(',', $weeks) . "))";
            }

            $where .= " AND (" . implode(' OR ', $clauses) . ")";
        }

        // --- NUEVA CONDICIÓN PARA PRIMA DOMINICAL ---
        if ($primaDominical == 'si') {
            // Filtramos extrayendo el valor del JSON igual a como lo haces en el SELECT
            $where .= " AND JSON_UNQUOTE(JSON_EXTRACT(employee_overtimes.overtimes, '$.sunday_premium')) = true";
        }else{
            $where .= " AND JSON_UNQUOTE(JSON_EXTRACT(employee_overtimes.overtimes, '$.sunday_premium')) = false";
        }
        // --------------------------------------------

        $sql = "
            SELECT
                employee_overtimes.*,
                employees.full_name AS employee_name,
                schedules.name AS horario,
                users.name AS aprobado_por,
                branch_offices.name as planta,
                m.name as nombremotivo,
                m.id as idMotivo,
                employee_overtimes.motivo,
                employee_overtimes.week_number,
                m.description as descripcionmotivo,
                JSON_UNQUOTE(JSON_EXTRACT(employee_overtimes.overtimes, '$.sunday_premium')) AS sunday_premium
            FROM employee_overtimes
            INNER JOIN branch_offices ON branch_offices.id = employee_overtimes.branch_office_id
            INNER JOIN employees ON employees.id = employee_overtimes.employee_id
            LEFT JOIN users ON employee_overtimes.approved_by = users.id
            LEFT JOIN schedules ON employee_overtimes.schedule_id = schedules.id
            LEFT JOIN motivos m ON m.id = employee_overtimes.motivo
            $where
            ORDER BY employee_overtimes.date DESC
        ";
        //dd($sql);

        return DB::select($sql);
    }


}
