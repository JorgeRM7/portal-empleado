<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssistenceOverTimes extends Model
{
    //
    protected $table = 'hikcentral';

    public static function index($data){
        $branch_office_id = $data['branch_office_id'] ?? null;
        $dateStart        = $data['dateStart'] ?? null;
        $dateEnd          = $data['dateEnd'] ?? null;
        $employees        = $data['employees'] ?? [];

        $where = "employee_shift_roles.active = 1 AND employee_shift_roles.deleted_at is null AND hikcentral.device_serial_no='F76890974'";
    
        if (!empty($employees)) {
            $employees = array_map('intval', $employees);
            $employeesList = implode(',', $employees);

            $where .= " AND hikcentral.employee_id IN ($employeesList)";
        }
    
        if (!empty($dateStart)) {
            $where .= " AND hikcentral.access_date >= '$dateStart'";
        }
        
        if (!empty($dateEnd)) {
            $where .= " AND hikcentral.access_date <= '$dateEnd'";
        }

        $sql = "
            SELECT 
                hikcentral.*, 
                employees.full_name, 
                employees.profile_photo_path, 
                shift_roles.name as rol
            FROM hikcentral
            INNER JOIN employees 
                ON employees.id = hikcentral.employee_id
            INNER JOIN employee_shift_roles 
                ON employee_shift_roles.employee_id = hikcentral.employee_id
            INNER JOIN shift_roles 
                ON shift_roles.id = employee_shift_roles.shift_role_id
            WHERE $where
            ORDER BY hikcentral.access_date DESC
            LIMIT 500
        ";

        return DB::select($sql);
    }
}
