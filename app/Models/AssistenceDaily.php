<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
class AssistenceDaily extends Model
{
    use SoftDeletes;

    protected $table = 'assistances';

    public static function index( $data ){
        $where = "assistances.deleted_at IS NULL";
        $branch_office_id = $data['branch_office_id'];
        $employees = $data['employees'];
        $date_start = $data['date_start'];
        $date_end = $data['date_end'];
        $schedules = $data['schedules'];
        $incidences = $data['incidences'];

        $where = "assistances.deleted_at IS NULL";

        if (!empty($employees) && is_array($employees)) {
            $ids = implode(',', array_map('intval', $employees));
            $where .= " AND assistances.employee_id IN ($ids)";
        }

        if (!empty($incidences) && is_array($incidences)) {
            $ids = implode(',', array_map('intval', $incidences));
            $where .= " AND assistances.incidence_id IN ($ids)";
        }

        if (!empty($schedules) && is_array($schedules)) {
            $ids = implode(',', array_map('intval', $schedules));
            $where .= " AND assistances.shift_role_id IN ($ids)";
        }

        if (!empty($branch_office_id)) {
            $where .= " AND assistances.branch_office_id IN ($branch_office_id)";
        }

        if (!empty($date_start) && !empty($date_end)) {
            $where .= " AND assistances.date BETWEEN '$date_start' AND '$date_end'";
        }

        
        $sql = "SELECT 
                    employees.id AS employee_id,
                    employees.full_name AS employee_name,
                    assistances.`date`,
                    assistances.week_number,
                    assistances.week_year,
                    assistances.entrance_time,
                    assistances.leave_time,
                    incidences.code AS incidence_code,
                    incidences.name AS incidence_name,
                    incidences.color AS incidence_color,
                    shift_roles.name AS shift_role 
                FROM assistances 
                INNER JOIN incidences ON incidences.id = assistances.incidence_id 
                INNER JOIN employees ON employees.id = assistances.employee_id 
                INNER JOIN shift_roles ON shift_roles.id = assistances.shift_role_id
                WHERE $where";
        return DB::select($sql);
    }

}
