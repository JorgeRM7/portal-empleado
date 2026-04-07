<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Motivo;
use App\Models\Position;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Overtimes
{
    public function index()
    {
        $branchOffices = BranchOffice::query()->select('id', 'name', 'code')->where('active', '=', 1)->get();
        $departments = Departments::select('id', 'name')->get();
        $employees = Employee::query()->select('id', 'full_name', 'branch_office_id')->where('status', '!=', 'termination')->get();
        $positions = Position::select('id', 'name')->get();
        $schedules = Schedules::select('id', 'name')->get();
        $motivos = Motivo::query()->select('id', 'name','description')->get();
        return Inertia::render('EmployeeOvertimes/Index', [
            'branchOffices' => $branchOffices,
            'departments' => $departments,
            'employees' => $employees,
            'positions' => $positions,
            'schedules' => $schedules,
            'motivos' => $motivos
        ]);
    }

    public function create()
    {
        
    }
}
