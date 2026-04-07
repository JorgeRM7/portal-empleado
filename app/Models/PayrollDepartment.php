<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PayrollDepartment extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_departments';

    protected $primaryKey = 'id';

    protected $fillable = [
        'in_file',
        'out_file',
        'out_file_employee',
        'meta',
        'year',
        'week',
        'month',
        'date',
        'user_id',
        'branch_office_id',
        'department_id',
        'external_id',
        'processing_at',
        'cancelled_at',
        'completed_at',
        'log_id',
        'payroll_type_id',
        'documents_processed_at',
        'credit',
        'debit',
        'employees',
        'total',
        'pdf_path',
        'preview_xml_path',
        'file_total',
        'file_individual'
    ];

    protected $casts = [
        'meta'                   => 'array',
        'date'                   => 'date',
        'processing_at'          => 'datetime',
        'cancelled_at'           => 'datetime',
        'completed_at'           => 'datetime',
        'documents_processed_at' => 'datetime',
        'credit'                 => 'decimal:2',
        'debit'                  => 'decimal:2',
        'total'                  => 'decimal:2'
    ];


    public function payrollType()
    {
        return $this->belongsTo(PayrollTypes::class, 'payroll_type_id');
    }

    public function payrollBranchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }
}