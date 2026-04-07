<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceClass extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_classes';

    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
    ];
}
