<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceAccountingList extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_accounting_lists';

    protected $fillable = [
        'name',
        'code',
    ];
}
