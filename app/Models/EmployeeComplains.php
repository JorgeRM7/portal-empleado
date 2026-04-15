<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeComplains extends Model
{
    use SoftDeletes;
    protected $table = 'employee_complains';

    protected $fillable = [
        'case',
        'subject',
        'response',
        'date',
        'hour',
        'branch_office_id',
        'employee_id',
        'path_complain'
    ];

    public static function filterComplaints($filtros = [])
    {
        $employeeId = $filtros['employee_id'] ?? null;
        $startDate  = $filtros['startDate'] ?? null;
        $endDate    = $filtros['endDate'] ?? null;
        $status     = $filtros['status'] ?? null;
        $subject    = $filtros['subject'] ?? null;

        $statusMap = [
            'PE' => 'Pendiente',
            'ES' => 'Escalado',
            'RE' => 'Resuelto'
        ];

        if (!empty($status) && isset($statusMap[$status])) {
            $status = $statusMap[$status];
        }

        $sql = "
            SELECT
                id,
                employee_id,
                `case`,
                subject,
                response,
                date,
                hour,
                branch_office_id,
                status,
                path_complain
            FROM employee_complains
            WHERE deleted_at IS NULL
        ";

        if (!empty($employeeId)) {
            $sql .= " AND employee_id = $employeeId";
        }

        if (!empty($startDate)) {
            $sql .= " AND date >= '$startDate'";
        }

        if (!empty($endDate)) {
            $sql .= " AND date <= '$endDate'";
        }

        if (!empty($status)) {
            $sql .= " AND status = '$status'";
        }

        if (!empty($subject)) {
            $sql .= " AND subject LIKE '%$subject%'";
        }

        $sql .= " ORDER BY date DESC, hour DESC";

        return DB::select($sql);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeComplainsAsigments::class, 'employee_complain_id');
    }
}
