<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeOvertimeEstimate extends Model
{
    use SoftDeletes;
    protected $table = 'employee_overtimes_estimate';
    protected $fillable = [
        'branch_office_id',
        'number_employees',
        'position_id',
        'schedule_id',
        'week',
        'complete_turn',
        'current_turn',
        'overtime',
        'triple_overtime',
        'double_overtime',
        'coment',
        'approved_at',
        'approved_by',
        'declined_at',
        'declined_by',
        'motivo'
    ];

    public static function index($branch_office_id, $position, $week, $status)
    {
        $sql = "SELECT
                    employee_overtimes_estimate.id,
                    positions.name AS posicion,
                    employee_overtimes_estimate.overtime,
                    employee_overtimes_estimate.week,
                    employee_overtimes_estimate.double_overtime,
                    employee_overtimes_estimate.triple_overtime,
                    employee_overtimes_estimate.current_turn,
                    employee_overtimes_estimate.complete_turn,
                    schedules.name AS turno,
                    aprobador.name AS aprobado_por,
                    declinador.name AS declinado_por,
                    employee_overtimes_estimate.coment,
                    employee_overtimes_estimate.number_employees,
                    positions.daily_salary,
                    employee_overtimes_estimate.approved_at,
                    employee_overtimes_estimate.declined_at,
                    employee_overtimes_estimate.motivo,
                    m.name as nombremotivo
                FROM employee_overtimes_estimate
                INNER JOIN branch_offices ON branch_offices.id = employee_overtimes_estimate.branch_office_id
                INNER JOIN positions ON positions.id = employee_overtimes_estimate.position_id
                LEFT JOIN schedules ON schedules.id = employee_overtimes_estimate.schedule_id
                LEFT JOIN users AS aprobador ON aprobador.id = employee_overtimes_estimate.approved_by
                LEFT JOIN users AS declinador ON declinador.id = employee_overtimes_estimate.declined_by
                LEFT JOIN motivos AS m ON m.id = employee_overtimes_estimate.motivo
                WHERE employee_overtimes_estimate.deleted_at IS NULL";
        if($branch_office_id != null){
            $sql .= " AND employee_overtimes_estimate.branch_office_id = $branch_office_id";
        }
        if($position != null){
            $sql .= " AND employee_overtimes_estimate.position_id = $position";
        }
        if($week != null){
            $sql .= " AND employee_overtimes_estimate.week = '$week'";
        }
        if (!empty($status)) {
            if ($status === 'aprobado') {
                $sql .= " AND employee_overtimes_estimate.approved_at IS NOT NULL";
            } elseif ($status === 'rechazado') {
                $sql .= " AND employee_overtimes_estimate.declined_at IS NOT NULL";
            } elseif ($status === 'pendiente') {
                $sql .= " AND employee_overtimes_estimate.approved_at IS NULL AND employee_overtimes_estimate.declined_at IS NULL";
            }
        }
        //dd($sql);
        return DB::select($sql);
    }
}