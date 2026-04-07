<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;

    protected $table = 'positions';

    protected $fillable = [
        'id',
        'name',
        'daily_salary',
        'compensation_in_trial',
        'daily_salary_in_trial',
        'type',
        'description',
        'pa_in_trial',
        'pp_in_trial',
        'pa_adjust',
        'pp_adjust',
        'perceptions_in_trial',
        'perceptions_adjust',
        'net_in_trial',
        'net_in_adjust',
        'compensations_adjust',
        'type_adjust',
    ];
}
