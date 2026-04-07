<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Departments extends Model
{
    use SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'id',
        'external_id',
        'name',
        'description',
        'image',
        'holiday',
        'payroll_type_id',
    ];

    protected $casts = [
        'holiday' => 'boolean',
    ];

    // tabla principal alias
    public static function index()
    {
        return DB::select("
            SELECT 
                d.id,
                d.id AS clave,
                d.external_id AS id_externo,
                d.name AS nombre,
                d.description AS descripcion,
                d.holiday AS festivo,
                pt.name AS tipo_asiento
            FROM departments d
            LEFT JOIN payroll_types pt ON
                pt.id = d.payroll_type_id
            WHERE d.deleted_at IS NULL
            ORDER BY d.id ASC
        ");
    }
}
