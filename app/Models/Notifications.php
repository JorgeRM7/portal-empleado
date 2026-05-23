<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Notifications extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'branch_office_id',
        'notification_date',
        'employee_id',
        'employee_full_name',
        'type_notification',
        'module',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'notification_date' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public static function index($data)
    {
        $startDate          = $data['start_date'] ?? null;
        $endDate            = $data['end_date'] ?? null;
        $branchOfficeId     = $data['branch_office_id'] ?? null;
        $module             = $data['module'] ?? null;
        $employee_id        = $data['employee_id'] ?? null;
        $status             = $data['status'] ?? null;

        $id = Auth::id();

        $where = " notifications.notifiable_id = ".$id;

        // fechas, por planta, por modulo, por empleado.

        // FECHA INICIO
        if (!empty($startDate) && empty($endDate)) {
            $where .= " AND DATE(notification_date) = '$startDate'";
        }

        // FECHA INICIO Y FIN
        if (!empty($startDate) && !empty($endDate)) {
            $where .= " AND DATE(notification_date) BETWEEN '$startDate' AND '$endDate'";
        }

        // SOLO FECHA FIN
        if (empty($startDate) && !empty($endDate)) {
            $where .= " AND DATE(notification_date) = '$endDate'";
        }

        // PLANTA
        if (!empty($branchOfficeId)) {
            $ids = is_array($branchOfficeId)
                ? implode(',', array_map('intval', $branchOfficeId))
                : intval($branchOfficeId);

            $where .= " AND branch_office_id IN ($ids)";
        }

        // MODULO
        if (!empty($module)) {

            if (is_string($module)) {
                $module = json_decode($module, true);
            }

            $module = is_array($module) ? $module : [$module];

            $modules = array_map(function ($item) {
                return "'" . addslashes($item) . "'";
            }, $module);

            $where .= " AND module IN (" . implode(',', $modules) . ")";
        }

        if (!empty($status)) {

            if (is_string($status)) {
                $status = json_decode($status, true);
            }

            $module = is_array($status) ? $status : [$status];

            $status = array_map(function ($item) {
                return "'" . addslashes($item) . "'";
            }, $status);

            $where .= " AND status IN (" . implode(',', $status) . ")";
        }

        // EMPLEADO
        if (!empty($employee_id)) {

            $ids = is_array($employee_id)
                ? implode(',', array_map('intval', $employee_id))
                : intval($employee_id);

            $where .= " AND notifications.employee_id IN ($ids)";
        }

        $sql = "
            SELECT
                notifications.*,
                branch_offices.code AS branch_office_code,
                users.name AS employee_name
            FROM notifications
            INNER JOIN branch_offices
                ON branch_offices.id = notifications.branch_office_id
            INNER JOIN users
                ON users.id = notifications.notifiable_id
            WHERE $where
            ORDER BY notification_date DESC
        ";

        return DB::select($sql);
    }
}
