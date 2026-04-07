<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BranchOfficeLocation extends Model
{
    use HasFactory;

    protected $table = 'branch_offices_locations';

    public $timestamps = false; // si no tienes timestamps

    protected $fillable = [
        'branch_office_id',
        'branch_office_netsuite_id',
        'ubicacion_id',
        'name'
    ];

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'ubicacion_id');
    }
}
