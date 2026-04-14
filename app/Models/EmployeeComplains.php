<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeComplains extends Model
{
    use SoftDeletes;
    protected $table = 'employee_complains';

    protected $fillable = [
        'case',
        'subject',
        'response',
        'date',
        'hour',
        'branch_office_id',
        'employee_id',
        'path_complain'
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeComplainsAsigments::class, 'employee_complain_id');
    }
}
