<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedules extends Model
{
    use SoftDeletes;

    protected $table = 'schedules';

    protected $fillable = [
        // ===== GENERAL =====
        'name',
        'entry_time',
        'leave_time',
        'normal_double_overtime',
        'normal_triple_overtime',
        // ===== TIEMPO EXTRA =====
        'double_overtime',
        'double_overtime_hour_value',
        'triple_overtime',
        'triple_overtime_hour_value',
        // ===== TOLERANCIAS =====
        'tolerance_before_entry_time',
        'tolerance_before_entry_type',
        'tolerance_after_entry_time',
        'tolerance_after_entry_type',
        'tolerance_before_leave_time',
        'tolerance_before_leave_type',
        'tolerance_after_leave_time',
        'tolerance_after_leave_type',
        // ===== TOLERANCIAS TIEMPO EXTRA =====
        'tolerance_overtime_before_entry_time',
        'tolerance_overtime_before_entry_type',
        'tolerance_overtime_after_entry_time',
        'tolerance_overtime_after_entry_type',
        'tolerance_overtime_before_leave_time',
        'tolerance_overtime_before_leave_type',
        'tolerance_overtime_after_leave_time',
        'tolerance_overtime_after_leave_type',
    ];

    // ===== GENERAL =====
    // id                                    --
    // name                                  --  Nombre del horario
    // entry_time                            --  Hora de entrada
    // leave_time                            --  Hora de salida
    // normal_double_overtime                --  Horas extra dobles
    // normal_triple_overtime                --  Horas extra triple

    // ===== TIEMPO EXTRA =====

    // double_overtime                       --  Horas extra dobles
    // double_overtime_hour_value            --  Formula hora extra doble
    // triple_overtime                       --  Horas extra triples
    // triple_overtime_hour_value            --  Formula hora extra triple

    // ===== TOLERANCIAS =====

    // tolerance_before_entry_time           --  Tolerancia antes de la entrada
    // tolerance_before_entry_type           --  Tipo tolerancia antes de la entrada
    // tolerance_after_entry_time            --  Tolerancia después de la entrada
    // tolerance_after_entry_type            --  Tipo tolerancia después de la entrada

    // tolerance_before_leave_time           --  Tolerancia antes de la salida
    // tolerance_before_leave_type           --  Tipo tolerancia antes de la salida
    // tolerance_after_leave_time            --  Tolerancia después de la salida
    // tolerance_after_leave_type            --  Tipo tolerancia después de la salida

    // ===== TOLERANCIAS TIEMPO EXTRA =====

    // tolerance_overtime_before_entry_time  --  Tolerancia antes de la entrada
    // tolerance_overtime_before_entry_type  --  Tipo tolerancia antes de la entrada
    // tolerance_overtime_after_entry_time   --  Tolerancia después de la entrada
    // tolerance_overtime_after_entry_type   --  Tipo tolerancia después de la entrada

    // tolerance_overtime_before_leave_time  --  Tolerancia antes de la salida
    // tolerance_overtime_before_leave_type  --  Tipo tolerancia antes de la salida
    // tolerance_overtime_after_leave_time   --  Tolerancia después de la salida
    // tolerance_overtime_after_leave_type   --  Tipo tolerancia después de la salida


}
