<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollInvoiceType extends Model
{
    use SoftDeletes;
    protected $table = "payroll_invoices_types";

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'status'
    ];

}
