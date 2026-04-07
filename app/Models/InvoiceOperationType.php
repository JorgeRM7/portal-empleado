<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceOperationType extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_operation_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'active',
        'branch_office_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'branch_office_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }
}