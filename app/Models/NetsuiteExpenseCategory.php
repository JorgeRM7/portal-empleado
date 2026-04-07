<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetsuiteExpenseCategory extends Model
{
    use SoftDeletes;

    protected $table = 'netsuite_expense_categories';

    protected $fillable = [
        'external_id',
        'name',
        'description',
        'account',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}