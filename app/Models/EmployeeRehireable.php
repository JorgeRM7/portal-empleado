<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class EmployeeRehireable extends Model
{
    protected $table = 'employee_rehireables';

    protected $fillable = [
        'employee_id',
        'branch_office_id',
        'comment',
        'date',
    ];

    public static function rehireEmployee($employeeId, $branchOfficeId, $comment)
    {

        return DB::transaction(function () use ($employeeId, $branchOfficeId, $comment) {

            $employee = Employee::findOrFail($employeeId);

            if (self::where('employee_id', $employeeId)->exists()) {
                throw new \Exception('Este empleado ya fue reingresado');
            }

            $rehire = self::create([
                'employee_id'      => $employeeId,
                'branch_office_id' => $branchOfficeId,
                'comment'          => $comment,
                'date'             => now()->toDateString(),
            ]);

            $employee->update([
                'rehireable' => 1,
                'branch_office_id' => $branchOfficeId,
            ]);

            return $rehire;
        });
    }
}
