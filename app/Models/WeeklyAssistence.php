<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WeeklyAssistence extends Model
{
    use SoftDeletes;
    protected $table = 'weekly_assistances';

    protected $fillable = [
        'monday_status',
        'tuesday_status',
        'wednesday_status',
        'thursday_status',
        'friday_status',
        'saturday_status',
        'sunday_status',

        'week_number',
        'week_year',
        'week_from',
        'week_to',

        'employee_id',
        'branch_office_id',

        'monday_data',
        'tuesday_data',
        'wednesday_data',
        'thursday_data',
        'friday_data',
        'saturday_data',
        'sunday_data',
    ];

    protected $casts = [
        'week_from' => 'date',
        'week_to'   => 'date',

        'monday_data'    => 'array',
        'tuesday_data'   => 'array',
        'wednesday_data' => 'array',
        'thursday_data'  => 'array',
        'friday_data'    => 'array',
        'saturday_data'  => 'array',
        'sunday_data'    => 'array',
    ];


    public static function index($filtros = []){
        $filtro_planta       = $filtros['planta'] ?? null;
        $filtro_departamento = $filtros['departamento'] ?? null;
        $ids_empleados       = $filtros['empleados'] ?? [];
        $filtro_semana       = $filtros['semana'] ?? null;
        $filtro_anio         = $filtros['anio'] ?? null;
        $filtro_incidencia   = $filtros['incidencia'] ?? null;

        if (empty($filtro_semana) || empty($filtro_anio)) {
            $fecha = Carbon::now('America/Mexico_City');

            $filtro_semana = $fecha->isoWeek();
            $filtro_anio   = $fecha->isoWeekYear();
        }


        $sql = "
            SELECT
                wa.id,
                wa.employee_id,
                wa.week_number,
                e.full_name AS employee_name,
                d.name AS department_name,
                p.name AS position_name,
                wa.week_year,
                mi.name AS monday_incidence,    mi.code AS monday_code,    mi.color AS monday_color,    mi.description AS monday_description,
                ti.name AS tuesday_incidence,   ti.code AS tuesday_code,   ti.color AS tuesday_color,   ti.description AS tuesday_description,
                wi.name AS wednesday_incidence, wi.code AS wednesday_code, wi.color AS wednesday_color, wi.description AS wednesday_description,
                thi.name AS thursday_incidence, thi.code AS thursday_code, thi.color AS thursday_color, thi.description AS thursday_description,
                fi.name AS friday_incidence,    fi.code AS friday_code,    fi.color AS friday_color,    fi.description AS friday_description,
                si.name AS saturday_incidence,  si.code AS saturday_code,  si.color AS saturday_color,  si.description AS saturday_description,
                sui.name AS sunday_incidence,   sui.code AS sunday_code,   sui.color AS sunday_color,   sui.description AS sunday_description,
                bo.code AS planta,
                wa.monday_data,
                wa.tuesday_data,
                wa.wednesday_data,
                wa.thursday_data,
                wa.friday_data,
                wa.saturday_data,
                wa.sunday_data,
                DATE_FORMAT(
                    GREATEST(
                        IFNULL(e.entry_date, NULL),
                        IFNULL(e.reentry_date, e.entry_date)
                    ),
                    '%d-%m-%Y'
                ) AS entry_date
            FROM weekly_assistances wa
            LEFT JOIN employees       e   ON wa.employee_id   = e.id
            LEFT JOIN departments     d   ON e.department_id  = d.id
            LEFT JOIN positions       p   ON e.position_id    = p.id
            LEFT JOIN incidences      mi  ON wa.monday_status    = mi.id
            LEFT JOIN incidences      ti  ON wa.tuesday_status   = ti.id
            LEFT JOIN incidences      wi  ON wa.wednesday_status = wi.id
            LEFT JOIN incidences      thi ON wa.thursday_status  = thi.id
            LEFT JOIN incidences      fi  ON wa.friday_status    = fi.id
            LEFT JOIN incidences      si  ON wa.saturday_status  = si.id
            LEFT JOIN incidences      sui ON wa.sunday_status    = sui.id
            LEFT JOIN branch_offices  bo  ON bo.id = e.branch_office_id
            WHERE e.status != 'termination'
            AND wa.deleted_at IS NULL
            AND wa.week_number = '$filtro_semana'
            AND wa.week_year   = '$filtro_anio'
        ";

        if (!empty($filtro_planta)) {
            $sql .= " AND wa.branch_office_id = '$filtro_planta'";
        }

        if (!empty($ids_empleados)) {
            $ids = implode(",", $ids_empleados);
            $sql .= " AND wa.employee_id IN ($ids)";
        }

        if (!empty($filtro_departamento)) {
            $ids = implode(",", $filtro_departamento);
            $sql .= " AND e.department_id IN ($ids)";
        }

        if (!empty($filtro_incidencia)) {
            $ids = implode(",", $filtro_incidencia);
            $sql .= " AND (
                wa.monday_status IN ( $ids) OR
                wa.tuesday_status IN ( $ids) OR
                wa.wednesday_status IN ($ids) OR
                wa.thursday_status IN ( $ids) OR
                wa.friday_status IN ($ids ) OR
                wa.saturday_status IN ($ids) OR
                wa.sunday_status IN ($ids)
            )";
        }

        $sql .= "
            GROUP BY wa.employee_id, wa.week_number
            ORDER BY wa.employee_id ASC
            LIMIT 5000
        ";
        $rows = DB::select($sql);

        return $rows;

    }

}
