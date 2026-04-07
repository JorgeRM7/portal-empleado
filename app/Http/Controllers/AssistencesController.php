<?php

namespace App\Http\Controllers;

use App\Models\Assistence;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\incidence;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AssistencesController
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request ){
        $user = auth()->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        return Inertia::render('Assistences/Index', [
            'branch_offices' => $branchOffices
        ]);
    }

    public function filter_data( Request $request ){
        
        $user = auth()->user();
        $fechaInicio = $request->fecha_inicio;
        $fechaFin    = $request->fecha_fin;

        $deparments =   Departments::select('id', 'name')->get();
        $data = Assistence::index([
            'planta'       => $request->planta,
            'departamento' => $request->departamento,
            'empleados'    => $request->empleados,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ]);
        $employees = Employee::select('id', 'full_name')->whereIn('status', ['entry', 'reentry'])
        ->where('branch_office_id', $request->planta)->get();

        $daysEs = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
        $data = collect($data)->map(function ($row) use ($daysEs) {
            $fecha = \Carbon\Carbon::parse($row->date);

            $row->dia_mes = $fecha->day;
            $row->dia_es  = $daysEs[$fecha->dayOfWeekIso];

            return $row;
        });

  
        return response()->json([
            'data'  => $data,
            'employees' => $employees,
            'deparments' => $deparments,
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
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        //
    }
}
