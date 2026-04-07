<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Benefit extends Model
{
    use SoftDeletes;

    protected $table = 'benefits';

    protected $fillable = [
        'name',
        'description',
        'conditioned',
        'each',
        'type',
        'conditioned_efficiency',
        'conditioned_seniority',
        'efficiency_rules',
        'day_cutoff',
    ];

    protected $casts = [
        'conditioned'            => 'boolean',
        'conditioned_efficiency' => 'boolean',
        'conditioned_seniority'  => 'boolean',
        'each'                   => 'integer',
        'day_cutoff'             => 'integer',
        'efficiency_rules'       => 'array',
    ];

    // tabla principal con la relacion de vinculacion
    public static function index()
    {
        return DB::select("
            SELECT 
                b.*,
                bci.quantity,
                bci.active,
                bci.category_incidence_id,
                ci.name AS category_incidence_name,
                CASE 
                    WHEN bci.benefit_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS has_incidence_relation
            FROM benefits b
            LEFT JOIN benefit_category_incidence bci ON 
                bci.benefit_id = b.id
            LEFT JOIN category_incidences ci ON 
                ci.id = bci.category_incidence_id
            WHERE b.deleted_at IS NULL
            ORDER BY b.id ASC
        ");
    }
    
    public static function show($id)
    {
        return collect(DB::select("
            SELECT 
                b.*,
                bci.quantity,
                bci.active,
                bci.category_incidence_id,
                ci.name AS category_incidence_name,
                CASE 
                    WHEN bci.benefit_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS has_incidence_relation
            FROM benefits b
            LEFT JOIN benefit_category_incidence bci 
                ON bci.benefit_id = b.id
            LEFT JOIN category_incidences ci 
                ON ci.id = bci.category_incidence_id
            WHERE b.deleted_at IS NULL
            AND b.id = ?
            LIMIT 1
        ", [$id]))->first();
    }
}
