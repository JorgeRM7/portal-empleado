<?php

namespace App\Http\Controllers;

use App\Models\EmployeeVacation;
use App\Models\BranchOffice;
use App\Models\Departments;
use App\Models\Employee;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmployeeVacationController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $departments = Departments::select('id', 'name')->get();

        return Inertia::render('EmployeesVacations/Index', [
            'branchOffices' => $branchOffices,
            'departments'   => $departments

        ]);

    }

    public function filter_data(Request $request)
    {
        // 1. Obtener los datos con el método estático que corregimos (Query en crudo)
        $dataRaw = EmployeeVacation::index_vacaciones([
            'semana'        => $request->semana,
            'planta'        => $request->branch_office_id,
            'empleados'     => $request->employees ?? [],
            'departamento'  => $request->department_id ?? [],
        ]);

        // 2. Obtener empleados para el select
        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->where('branch_office_id', $request->branch_office_id)
            ->get();

        // 3. Transformar los datos manteniendo la estructura de objetos
        $data = collect($dataRaw)->map(function ($item) {

            // --- Lógica de fecha del PHP puro replicada con Carbon ---
            $year = (int)$item->week_year;
            $week = (int)$item->week_number;

            // Creamos el 1 de enero del año correspondiente
            $fecha = Carbon::createFromDate($year, 1, 1);

            // Buscamos el primer lunes del año (Igual a tu (8 - $dia_semana) % 7)
            if ($fecha->dayOfWeek !== Carbon::MONDAY) {
                $fecha->next(Carbon::MONDAY);
            }

            // Sumamos las semanas (restamos 1 porque el primer lunes ya es la semana 1)
            if ($week > 1) {
                $fecha->addWeeks($week - 1);
            }

            // --- Asignamos las propiedades al objeto ---
            $item->fecha_calculada = $fecha->format('d/m/Y'); // Esta sustituye a tu $fecha
            $item->fecha_pago = '31/12/' . $fecha->format('Y');

            // Opcional: Si quieres enviar el badge ya armado como en el puro
            $item->badge_eliminado = $item->deleted_at
                ? '<span class="badge bg-label-danger">' . $item->deleted_at . '</span>'
                : '';

            return $item;
        });

        return response()->json([
            'data' => $data,
            'employees' => $employees
        ]);
    }

    public function filter_data_saldos(Request $request)
    {
        $data = EmployeeVacation::index_saldos([
            'planta'        => $request->branch_office_id,
            'empleados'     => $request->employees ?? [],
            'departamento'  => $request->department_id ?? [],
        ]);

        $data = collect($data)->map(function ($item) {
            $item->prima_vacacional = floatval($item->disponibles) * 0.25;
            return $item;
        });

        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->where('branch_office_id', $request->branch_office_id)
            ->get();

        return response()->json([
            'data' => $data,
            'employees' => $employees
        ]);
    }

    public function filter_data_movimientos(Request $request)
    {
        $data = EmployeeVacation::index_movimientos([
            'planta'        => $request->branch_office_id,
            'empleados'     => $request->employees ?? [],
        ]);

        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->where('branch_office_id', $request->branch_office_id)
            ->get();

        $totalDias = collect($data)->sum('dias_disfrute');

        return response()->json([
            'data' => $data,
            'employees' => $employees,
            'total_dias_disfrute' => $totalDias
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
