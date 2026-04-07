<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceTerm extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_terms';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];
}
