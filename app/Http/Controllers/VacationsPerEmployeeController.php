<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\VacationsPerEmployee;
use App\Models\Employee;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Logs;

class VacationsPerEmployeeController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('VacationsPerEmployee/Index', [
            'branchOffices' => $branchOffices
        ]);
    }

    public function filter_data_vacationsperEmployee(Request $request)
    {
        $branchOfficeId = $request->input('branch_office_id');
        $employeesIds   = $request->input('employees', []);

        $data = VacationsPerEmployee::index([
            'planta'    => $branchOfficeId,
            'empleados' => $employeesIds,
        ]);

        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->when($branchOfficeId, function ($query) use ($branchOfficeId) {
                $query->where('branch_office_id', $branchOfficeId);
            })
            ->orderBy('full_name')
            ->get();

        $data = collect($data);

        return response()->json([
            'data' => $data,
            'employees' => $employees,
            'total_dias_disfrute' => $data->sum('dias_disfrute')
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
    public function store(Request $request){

        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'amount'      => 'required|numeric|min:0',
            'date'        => 'required|date',
            'seniority'   => 'nullable|integer|min:0',
        ]);

        $result = VacationsPerEmployee::store($validated);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        Logs::create([
            'action'          => 'CREATE',
            'user_id'         => auth()->id(),
            'table_name'      => 'employee_day_vacations',
            'date'            => now(),
            'relationship_id' => $validated['employee_id'],
            'old_data'        => json_encode([
                'before' => null,
                'after'  => $validated
            ])
        ]);

        return response()->json($result, 201);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'amount'      => 'required|numeric|min:0',
            'seniority'   => 'required|numeric|min:0',
            'date'        => 'nullable|date',
        ]);

        $registro = VacationsPerEmployee::findById($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $before = [
            'employee_id' => $registro->employee_id,
            'amount'      => $registro->amount,
            'seniority'   => $registro->seniority,
            'date'        => $registro->date,
        ];

        $newData = [
            'employee_id' => $request->employee_id,
            'amount'      => $request->amount,
            'seniority'   => $request->seniority,
            'date'        => $request->date,
        ];

        VacationsPerEmployee::updateById($id, $newData);

        Logs::create([
            'action'          => 'UPDATE',
            'user_id'         => auth()->id(),
            'table_name'      => 'employee_day_vacations',
            'date'            => now(),
            'relationship_id' => $request->employee_id,
            'old_data'        => json_encode([
                'before' => $before,
                'after'  => $newData
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        $registro = VacationsPerEmployee::findById($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $before = (array) $registro;

        VacationsPerEmployee::softDeleteById($id);

        // 2. Crear el Log de eliminación
        Logs::create([
            'action'          => 'DELETE',
            'user_id'         => auth()->id(),
            'table_name'      => 'vacations_per_employees',
            'date'            => now(),
            'relationship_id' => $before['employee_id'] ?? null,
            'old_data'        => json_encode([
                'before' => $before,
                'after'  => null
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente'
        ]);
    }
}
