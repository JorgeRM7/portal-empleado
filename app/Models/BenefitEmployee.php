<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitEmployee extends Model
{
    protected $table = 'benefit_employee';

    public $timestamps = false;

    protected $fillable = [
        'benefit_id',
        'employee_id'
    ];
}
