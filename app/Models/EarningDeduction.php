<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EarningDeduction extends Model
{
    use SoftDeletes;
    
    protected $table = 'salary_payments';

    protected $fillable = [
        'code',
        'name',
        'description',
        'rules',
        'amount',
        'type',
        'code_complete',
        'apply_piecework',
        'apply',
        'file_id',
        'need_file',
        'food_pass',
        'medial_pass',
    ];
}
