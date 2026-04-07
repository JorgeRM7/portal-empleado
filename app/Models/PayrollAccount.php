<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAccount extends Model
{
    use SoftDeletes;
    protected $table = "payroll_accounts";

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'number',
        'currency',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

}
