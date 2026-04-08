<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PayrollInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_invoices';
    
    protected $fillable = [
        'pdf_path',
        'week',
        'year',
        'employee_id',
        'branch_office_id',
        'estatus_correo',
        'payroll_invoices_type_id',
        'send_correo'
    ];

    public static function index($filtro_empleado, $filtro_semana, $filtro_anio ){
        $sql = "SELECT 
                branch_offices.code AS planta,
                employees.id AS numero_nomina,
                employees.full_name AS nombre_empleado,
                payroll_invoices.week AS semana,
                payroll_invoices.year,
                payroll_invoices.extra,
                payroll_invoices.pdf_path,
                payroll_invoices.id,
                payroll_invoices.estatus_correo,
                payroll_invoices.send_correo,
                payroll_invoices.error_correo,
                payroll_invoices_types.name AS tipo_recibo
            FROM `payroll_invoices`
            INNER JOIN branch_offices ON branch_offices.id = payroll_invoices.branch_office_id
            INNER JOIN employees ON employees.id = payroll_invoices.employee_id
            LEFT JOIN payroll_invoices_types ON payroll_invoices_types.id = payroll_invoices.payroll_invoices_type_id
            WHERE payroll_invoices.deleted_at IS NULL AND payroll_invoices.employee_id = '" . intval($filtro_empleado) . "'
        ";
        $condiciones = [];
        
        if (!empty($filtro_semana)) {
            $condiciones[] = "payroll_invoices.week = '" . intval($filtro_semana) . "'";
        }
        if (!empty($filtro_anio)) {
            $condiciones[] = "payroll_invoices.year = '" . intval($filtro_anio) . "'";
        }
        
        if (count($condiciones) > 0) {
            $sql .= " AND " . implode(" AND ", $condiciones);
        }
    
        $sql .= "GROUP BY payroll_invoices.id ORDER BY payroll_invoices.id DESC";

        //dd($sql);

        return DB::select($sql);
    }
}
