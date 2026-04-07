<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceArticles extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_articles';

    protected $fillable = [
        'name',
        'code',
    ];
}
