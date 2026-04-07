<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Classification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classifications';

    protected $fillable = [
        'id',
        'branch_office_id',
        'classification',
        'description'
    ];

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

    public static function index(){
        $query = " SELECT 
                classifications.*,
                branch_offices.code as branch_office
            FROM classifications
            INNER JOIN branch_offices ON branch_offices.id = classifications.branch_office_id
            WHERE classifications.deleted_at IS NULL
        ";

        // return response()->json($query);
        return DB::select($query);
    }
}
