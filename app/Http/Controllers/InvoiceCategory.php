<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCategory extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];
}
