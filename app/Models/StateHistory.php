<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StateHistory extends Model
{
    use SoftDeletes;

    protected $table = 'employee_statuses';

    protected $fillable = [
        'status',
        'content',
        'date',
        'reason_id',
        'employee_id'
    ];

    public static function getData($id){
        $sql = "SELECT 
            es.id, 
            es.employee_id, 
            e.full_name AS employee_name, 
            esr.name AS reason_name, 
            es.status, 
            es.date, 
            es.content, 
            u.name AS user_name, 
            JSON_UNQUOTE(JSON_EXTRACT(es.values, '$.name')) AS `values`, 
            COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(es.values, '$.Planta Nueva')), 
                JSON_UNQUOTE(JSON_EXTRACT(es.values, '$.Planta Anterior'))
            ) AS planta, 
            es.created_at, 
            es.updated_at,
            es.reason_id,
            e.branch_office_id,
            e.profile_photo_path
        FROM employee_statuses es
        LEFT JOIN employees e ON es.employee_id = e.id
        LEFT JOIN employee_status_reasons esr ON es.reason_id = esr.id
        LEFT JOIN users u ON es.user_id = u.id
        WHERE es.deleted_at IS NULL 
        AND e.full_name IS NOT NULL  
        AND e.id = ?
        ORDER BY es.date DESC";

        return DB::select($sql, [$id]);
    }
}
