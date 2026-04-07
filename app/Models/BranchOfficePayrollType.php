<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchOfficePayrollType extends Model
{
    protected $table = "branch_office_payroll_type";

    public $timestamps = false;

    protected $fillable = [
        "payroll_type_id",
        "branch_office_id",
    ];
}
