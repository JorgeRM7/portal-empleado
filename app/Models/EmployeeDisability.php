<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeDisability extends Model
{
    use SoftDeletes;

    protected $table = 'employee_disabilities';

    protected $fillable = [
        'incidence_id',
        'employee_incidence_id',
        'status',
        'delivery_status',
        'delivery_notification_status',
        'shipping_comment',
    ];

    protected $casts = [
        'incidence_id' => 'integer',
        'employee_incidence_id' => 'integer',
    ];

    public static function index($data)
    {
        $where = [];
        $params = [];

        $where[] = "employee_incidences.incidence_id IN (4,5,6,7,8)";

        $branchOfficeIds = $data['selectedBranchOfficeId'] ?? [];

        if (is_string($branchOfficeIds)) {
            $branchOfficeIds = json_decode($branchOfficeIds, true) ?? [];
        }

        $branchOfficeIds = collect($branchOfficeIds)
            ->filter(fn ($id) => $id !== 'all' && is_numeric($id))
            ->map(fn ($id) => intval($id))
            ->values()
            ->toArray();

        if (!empty($branchOfficeIds)) {
            $placeholders = implode(',', array_fill(0, count($branchOfficeIds), '?'));

            $where[] = "employee_incidences.branch_office_id IN ($placeholders)";

            $params = array_merge($params, $branchOfficeIds);
        }

        $dateRange = $data['date'] ?? [];

        if (is_string($dateRange)) {
            $dateRange = json_decode($dateRange, true) ?? [];
        }

        $dateStart = !empty($dateRange[0])
            ? \Carbon\Carbon::parse($dateRange[0])->format('Y-m-d')
            : null;

        $dateEnd = !empty($dateRange[1])
            ? \Carbon\Carbon::parse($dateRange[1])->format('Y-m-d')
            : null;

        if ($dateStart && $dateEnd) {
            $where[] = "employee_incidences.validity_from BETWEEN ? AND ?";
            $params[] = $dateStart;
            $params[] = $dateEnd;
        }

        $statuses = $data['status'] ?? [];

        if (is_string($statuses)) {
            $statuses = json_decode($statuses, true) ?? [];
        }

        $statuses = collect($statuses)
            ->filter(fn ($status) => in_array($status, [
                'Pendiente envío',
                'Enviado',
                'Comentado',
                'Recibido',
                'Pendiente entrega',
                'Entregado',
            ]))
            ->values()
            ->toArray();

        if (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));

            $where[] = "(employee_disabilities.status IN ($placeholders) OR employee_disabilities.delivery_status IN ($placeholders))";
            $params = array_merge($params, $statuses, $statuses);
        }


        $employeeIds = $data['employees'] ?? [];

        if (is_string($employeeIds)) {
            $employeeIds = json_decode($employeeIds, true) ?? [];
        }

        $employeeIds = collect($employeeIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => intval($id))
            ->values()
            ->toArray();

        if (!empty($employeeIds)) {
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

            $where[] = "employees.id IN ($placeholders)";
            $params = array_merge($params, $employeeIds);
        }

        $where[] = "employee_incidences.deleted_at IS NULL AND employee_disabilities.deleted_at IS NULL";

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = " WHERE " . implode(" AND ", $where);
        }



        $sql = DB::select("
            SELECT
                employee_disabilities.id,
                employee_disabilities.status,
                employee_disabilities.delivery_status,
                employee_disabilities.delivery_notification_status,
                employees.status AS employee_status,
                IF(employees.email IS NOT NULL AND TRIM(employees.email) <> '', 1, 0) AS has_personal_email,
                CASE
                    WHEN employee_disabilities.delivery_status = 'Pendiente entrega'
                        AND employees.status = 'termination'
                    THEN 'Suspendido'
                    ELSE employee_disabilities.delivery_status
                END AS support_status,
                employee_disabilities.shipping_comment,
                employee_incidences.validity_from,
                employee_incidences.validity_to,
                employee_incidences.days,
                employee_incidences.week_number,
                employee_incidences.week_year,
                employee_incidences.comment,
                employee_incidences.file_path,
                employee_incidences.id AS incidence_id,
                employee_incidences.branch_office_id,
                employee_incidences.document_number,
                employees.full_name AS employee,
                employees.id AS employee_number,
                incidences.name AS incidence_name,
                branch_offices.code AS branch_office,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM risk_cases AS range_risk_cases
                        WHERE range_risk_cases.employee_id = employee_incidences.employee_id
                            AND range_risk_cases.deleted_at IS NULL
                            AND range_risk_cases.date_open IS NOT NULL
                            AND employee_incidences.validity_from >= range_risk_cases.date_open
                            AND (
                                range_risk_cases.date_end IS NULL
                                OR employee_incidences.validity_to <= range_risk_cases.date_end
                            )
                    )
                    THEN 'Sí'
                    ELSE 'No'
                END AS within_risk_case_range,
                IF(risk_cases.id IS NOT NULL, 1, 0) AS has_risk_case
            FROM employee_disabilities
            INNER JOIN employee_incidences
                ON employee_incidences.id = employee_disabilities.employee_incidence_id
            INNER JOIN employees
                ON employees.id = employee_incidences.employee_id
            INNER JOIN branch_offices
                ON branch_offices.id = employee_incidences.branch_office_id
            INNER JOIN incidences
                ON incidences.id = employee_disabilities.incidence_id
            LEFT JOIN risk_cases
                ON risk_cases.incidence_id = employee_incidences.id 
                AND risk_cases.deleted_at IS NULL
            $whereSql
            GROUP BY employee_incidences.id
            ORDER BY employee_incidences.validity_from DESC
        ", $params);

        return $sql;
    }



}
