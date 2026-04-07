<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BranchOffice extends Model
{

    use SoftDeletes;

    protected $table = 'branch_offices';

    protected $fillable = [
        'internal_code',
        'code',
        'name',
        'active',
        'external_location_id',
        'company_id',
        'has_employee_classes',
        'employee_classes',
        'payroll_location_id',
        'is_sales',
    ];
    
    // Select solo para index
    public static function indexList()
    {
        return DB::select("
            SELECT 
                bo.id,
                bo.code AS codigo,
                bo.name AS planta,
                co.name AS empresa,
                JSON_UNQUOTE(json_extract(bo.meta, '$.tax_id')) AS rfc,
                bo.internal_code AS clave_netsuite,
                bo.external_location_id AS clave_ubicacion_netsuite,
                CASE 
                    WHEN bok.certificate_path IS NOT NULL 
                        AND bok.certificate_path <> '' 
                    THEN 1 
                    ELSE 0 
                END AS tiene_certificado_fiscal,
                DATE_FORMAT(bok.expires_at, '%Y-%m-%d') AS expiracion_clave
            FROM branch_offices bo
            LEFT JOIN companies co 
                ON bo.company_id = co.id
            LEFT JOIN branch_office_fiscal_keys bok 
                ON bo.id = bok.branch_office_id
            WHERE bo.deleted_at IS NULL
            ORDER BY bo.id
        ");
    }
    
    // Select edit y show
    public static function show($id)
    {
        return DB::selectOne("
            SELECT 
                bo.*,
                bok.certificate_path,
                bok.key_path,
                bok.passphrase,
                bok.expires_at
            FROM branch_offices bo
            LEFT JOIN branch_office_fiscal_keys bok 
                ON bo.id = bok.branch_office_id
            WHERE bo.id = ?
        ", [$id]);
    }

    public function users(){
        return $this->belongsToMany(
            User::class,
            'branch_office_user',
            'branch_office_id',
            'user_id'
        );
    }

}
