<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Incidence extends Model
{

    use SoftDeletes;

    protected $table = 'incidences';

    protected $fillable = [
        // ===== GENERAL =====
        'name',
        'code',
        'category_incidence_id',
        'external_code',
        'incapacity_code',
        'color',
        'description',

        // ===== REGLAS =====
        'active',
        'read_only',
        'requires_document',
        'requires_code',
        'requires_auth',
        'requested_by_user',

        // ===== CONFIG TIEMPOS =====
        'requires_advance',
        'advance_each',
        'advance_type',
        'requires_schedule',
        'requires_date',
        'requires_rest_date',

        // ===== MULTIMEDIA =====
        'url_video',
    ];

    protected $casts = [
        'active' => 'boolean',
        'read_only' => 'boolean',
        'requires_document' => 'boolean',
        'requires_code' => 'boolean',
        'requires_auth' => 'boolean',
        'requested_by_user' => 'boolean',
        
        'requires_advance' => 'boolean',
        'requires_schedule' => 'boolean',
        'requires_date' => 'boolean',
        'requires_rest_date' => 'boolean',
    ];
}
