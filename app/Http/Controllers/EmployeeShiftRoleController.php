<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Employee;
use App\Models\EmployeeShiftRole;
use App\Models\Logs;
use App\Models\Schedules;
use App\Models\ShiftRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeShiftRoleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        return Inertia::render('EmployeeShiftRoles/Index', [
            'employees' => $employees,
            'branch_offices' => $branchOffices,
        ]);
    }

    public function getEmployeeShiftRoles(Request $request)
    {
        return EmployeeShiftRole::getEmployeeShiftRoles($request->employee_id, $request->eliminated, $request->branch_office, $request->status);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $shifts = ShiftRole::all();
        return Inertia::render('EmployeeShiftRoles/Create', [
            'employees' => $employees,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required',
            'shift_role_id' => 'required',
            'start_date' => 'required',
        ]);

        $shiftRole = EmployeeShiftRole::create([
            'employee_id' => $validated['employee_id'],
            'shift_role_id' => $validated['shift_role_id'],
            'next_shift_role_id' => $request->next_shift_role_id,
            'start_date' => $validated['start_date'],
            'end_date' => $request->end_date,
            'active' => $request->active,
        ]);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_shift_roles',
            'date' => Carbon::now(),
            'relationship_id' => $shiftRole->id
        ]);

        return redirect()->route('employee-shift-roles');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeShiftRole $employeeShiftRole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeShiftRole $employeeShiftRole)
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $shifts = ShiftRole::all();
        return Inertia::render('EmployeeShiftRoles/Edit', [
            'employees' => $employees,
            'shifts' => $shifts,
            'shifRole' => $employeeShiftRole,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeShiftRole $employeeShiftRole)
    {
        //dd($request->all());
        $validated = $request->validate([
            'employee_id' => 'required',
            'shift_role_id' => 'required',
            'start_date' => 'required',
        ]);

        $oldData = $employeeShiftRole->getOriginal();

        $employeeShiftRole->update([
            'employee_id' => $validated['employee_id'],
            'shift_role_id' => $validated['shift_role_id'],
            'next_shift_role_id' => $request->next_shift_role_id,
            'start_date' => $validated['start_date'],
            'end_date' => $request->end_date,
            'active' => $request->active,
        ]);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old_data' => json_encode($oldData),
            'table_name' => 'employee_shift_roles',
            'date' => Carbon::now(),
            'relationship_id' => $employeeShiftRole->id
        ]);

        return redirect()->route('employee-shift-roles');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeShiftRole $employeeShiftRole)
    {
        $employeeShiftRole->delete();
        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_shift_roles',
            'date' => Carbon::now(),
            'relationship_id' => $employeeShiftRole->id
        ]);
        
        return redirect()->route('employee-shift-roles');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->ids;
        //dd($ids);
        EmployeeShiftRole::whereIn('id', $ids)->delete();

        $records = EmployeeShiftRole::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DELETE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_shift_roles',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        return redirect()->route('employee-shift-roles');
    }
}
