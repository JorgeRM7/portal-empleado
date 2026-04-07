<?php

namespace App\Http\Controllers;

use App\Imports\EmployeeCompensationImport;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\Logs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class EmployeeCompensationController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Departments::select('id', 'name')->get();
        return Inertia::render("Compensations/Index", [
            'departments' => $departments,
        ]);
    }

    public function filterData(Request $request)
    {
        $approved = $request->query('approved');
        $approved = $approved ? filter_var($approved, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : false;
        $employees = EmployeeCompensation::getCompensations($request->branchOfficeId, $request->departmentId, $request->week, $request->year, $approved, $request->employeeId);
        return $employees;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
            'branch_office_id' => ['required']
        ]);

        try {
            Excel::import(new EmployeeCompensationImport($request->branch_office_id), $request->file('file'));

            return redirect('/employee/compensations');
        } catch (ValidationException $e) {
            return redirect()->back()
                    ->withErrors(['file' => 'El archivo contiene filas inválidas.'])
                    ->with('import_failures', collect($e->failures())->map(fn ($f) => [
                        'row' => $f->row(),
                        'attribute' => $f->attribute(),
                        'errors' => $f->errors(),
                        'values' => $f->values(),
                    ])->values()->toArray());
        }

        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $employees = Employee::select('id', 'full_name')->where('branch_office_id', $request->branchOfficeId)->orderBy('id', 'ASC')->get();

        return Inertia::render('Compensations/Create', [
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required',
            'week_number' => 'required',
            'week_year' => 'required',
            'percent' => 'required|numeric|min:1|max:100',
            'compensation' => 'required|numeric|min:1',
        ]);

        $transport = $request->transport ? $request->transport : 0;
        $piece_work = $request->piece_work ? $request->piece_work : 0;
        $extra_compensation = $request->extra_compensation ? $request->extra_compensation : 0;

        $compensation = EmployeeCompensation::create([
            'employee_id'=> $validated['employee_id'],
            'week_number'=> $validated['week_number'],
            'week_year'=> $validated['week_year'],
            'percent'=> $validated['percent'],
            'compensation'=> $validated['compensation'],
            'transport'=> $transport,
            'piece_work'=> $piece_work,
            'extra_compensation'=> $extra_compensation,
            'total'=> $request->total,
            'comment'=> $request->comment,
            'branch_office_id'=> $request->branch_office_id,
            'salary_payments'=> $request->salary_payments,
        ]);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_compensation',
            'date' => Carbon::now(),
            'relationship_id' => $compensation->id
        ]);

        return redirect()->route('/compensations');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeCompensation $employeeCompensation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeCompensation $compensation, Request $request)
    {
        $employees = Employee::select('id', 'full_name')->where('branch_office_id', $request->branchOfficeId)->get();
        return Inertia::render('Compensations/Edit', [
            'compensation' => $compensation,
            'employees' => $employees,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeCompensation $compensation)
    {
        $validated = $request->validate([
            'employee_id' => 'required',
            'week_number' => 'required',
            'week_year' => 'required',
            'percent' => 'required|numeric|min:1|max:100',
            'compensation' => 'required|numeric|min:1',
        ]);

        $transport = $request->transport ? $request->transport : 0;
        $piece_work = $request->piece_work ? $request->piece_work : 0;
        $extra_compensation = $request->extra_compensation ? $request->extra_compensation : 0;

        $compensation->update([
            'employee_id'=> $validated['employee_id'],
            'week_number'=> $validated['week_number'],
            'week_year'=> $validated['week_year'],
            'percent'=> $validated['percent'],
            'compensation'=> $validated['compensation'],
            'transport'=> $transport,
            'piece_work'=> $piece_work,
            'extra_compensation'=> $extra_compensation,
            'total'=> $request->total,
            'comment'=> $request->comment,
            'branch_office_id'=> $request->branch_office_id,
            'salary_payments'=> $request->salary_payments,
        ]);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_compensation',
            'date' => Carbon::now(),
            'relationship_id' => $compensation->id
        ]);

        return redirect()->route('/compensations');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeCompensation $compensation)
    {
        $compensation->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_compensation',
            'date' => Carbon::now(),
            'relationship_id' => $compensation->id
        ]);
        return redirect()->route('/compensations');
    }

    public function destroyMultiple(Request $request)
    {
        EmployeeCompensation::whereIn('id', $request->ids)->delete();

        $records = EmployeeCompensation::whereIn('id', $request->ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DELETE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_compensation',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        return redirect()->route('/compensations');
    }

    public function approve(Request $request)
    {
        $today = Carbon::now();
        EmployeeCompensation::where('id', $request->id)->update(['approved_by' => Auth::user()->id, 'approved_at' => $today]);

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_compensation',
            'date' => Carbon::now(),
            'relationship_id' => $request->id
        ]);
        return redirect()->route('/compensations');
    }

    public function reject(Request $request)
    {
        $today = Carbon::now();
        EmployeeCompensation::where('id', $request->id)->update(['declined_by' => Auth::user()->id, 'declined_at' => $today]);

        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_compensation',
            'date' => Carbon::now(),
            'relationship_id' => $request->id
        ]);
        return redirect()->route('/compensations');
    }

    public function rejectAll(Request $request)
    {
        $today = Carbon::now();
        EmployeeCompensation::whereIn('id', $request->ids)->update(['declined_by' => Auth::user()->id, 'declined_at' => $today]);

        $records = EmployeeCompensation::whereIn('id', $request->ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DECLINED',
                'user_id' => Auth::id(),
                'table_name' => 'employee_compensation',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        return redirect()->route('/compensations');
    }

    public function approveAll(Request $request)
    {
        $today = Carbon::now();
        EmployeeCompensation::whereIn('id', $request->ids)->update(['approved_by' => Auth::user()->id, 'approved_at' => $today]);

        $records = EmployeeCompensation::whereIn('id', $request->ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_compensation',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        return redirect()->route('/compensations');
    }
}
