<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hikcentral extends Model
{
    // use SoftDeletes;

    protected $table = 'hikcentral';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false; 


    protected $fillable = [
        'employee_id',
        'access_date_and_time',
        'access_date',
        'access_time',
        'authentication_result',
        'authentication_type',
        'device_name',
        'device_serial_no',
        'reader_name',
        'first_name',
        'last_name',
        'person_name',
        'person_group',
        'card_number',
        'direction',
        'skin_surface_temperature',
        'temperature_status',
        'wearing_mask_or_not',
        'attendance_status'
    ];

    protected $casts = [
        'access_date_and_time' => 'datetime',
        'access_date' => 'date',
        'access_time' => 'datetime:H:i:s',
        'deleted_at' => 'datetime'
    ];
}
