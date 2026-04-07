<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeStatusReason extends Model
{
    use SoftDeletes;

    protected $table = 'employee_status_reasons';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'type',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function employeeStatuses()
    {
        return $this->hasMany(EmployeeStatuse::class, 'reason_id');
    }
}