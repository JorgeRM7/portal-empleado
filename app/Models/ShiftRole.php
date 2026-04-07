<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftRole extends Model
{
    use SoftDeletes;
    
    protected $table = 'shift_roles';

    protected $fillable = [
        'name',
        'dynamic',
        'rules',
        'monday_schedule_id',
        'monday_overtimes',
        'tuesday_schedule_id',
        'tuesday_overtimes',
        'wednesday_schedule_id',
        'wednesday_overtimes',
        'thursday_schedule_id',
        'thursday_overtimes',
        'friday_schedule_id',
        'friday_overtimes',
        'saturday_schedule_id',
        'saturday_overtimes',
        'sunday_schedule_id',
        'sunday_overtimes',
        'holiday',
    ];
}
