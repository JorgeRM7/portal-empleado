<?php

namespace App\Http\Controllers;

use App\Models\EmployeeNoRehirable;
use App\Models\BranchOffice;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\EmployeeRehireable;
use App\Models\Logs;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class EmployeeNoRehirableController
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $user = auth()->user();

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('EmployeesNoRehirable/Index', [
            'branchOffices' => $branchOffices
        ]);
    }


    public function data(Request $request)
    {
        $data = Employee::noRehirable([
            'planta'        => $request->branch_office_id,
            'employees'     => $request->employees,
            'ingreso_desde' => $request->ingreso_desde,
            'ingreso_hasta' => $request->ingreso_hasta,
            'termino_desde' => $request->termino_desde,
            'termino_hasta' => $request->termino_hasta,
        ]);

        $employeesList = Employee::select(
            'employees.id',
            'employees.full_name'
        )
        ->join('employee_statuses', 'employee_statuses.employee_id', '=', 'employees.id')
        ->join('employee_status_reasons', 'employee_status_reasons.id', '=', 'employee_statuses.reason_id')

        ->where('employees.status', 'termination')
        ->where('employees.rehireable', 0)
        ->where('employee_status_reasons.type', 'BAJA')

        ->when($request->branch_office_id, function ($query) use ($request) {
            $ids = is_array($request->branch_office_id)
                ? $request->branch_office_id
                : [$request->branch_office_id];

            return $query->whereIn('employees.branch_office_id', $ids);
        })

        ->when($request->ingreso_desde && $request->ingreso_hasta, function ($q) use ($request) {
            return $q->whereBetween('employees.entry_date', [$request->ingreso_desde, $request->ingreso_hasta]);
        })
        ->when($request->termino_desde && $request->termino_hasta, function ($q) use ($request) {
            return $q->whereBetween('employees.termination_date', [$request->termino_desde, $request->termino_hasta]);
        })

        ->orderBy('employees.id', 'asc')
        ->distinct()
        ->get();

        return response()->json([
            'data' => $data,
            'employees' => $employeesList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $employee = Employee::findOrFail($id);

        return response()->json($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $before = [
                'rehireable' => $employee->rehireable
            ];

            $employee->update([
                'rehireable' => 1
            ]);

            $changes = $employee->getChanges();

            if (!empty($changes)) {
                Logs::create([
                    'action'          => 'UPDATE',
                    'user_id'         => auth()->id(),
                    'table_name'      => $employee->getTable(),
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode([
                        'before' => $before,
                        'after'  => $changes
                    ])
                ]);
            }

            return response()->json([
                'message' => 'Empleado marcado como recontratable correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
