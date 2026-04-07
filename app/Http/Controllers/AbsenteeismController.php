<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Absenteeism;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\incidence;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AbsenteeismController
{
    /**
     * Display a listing of the resource.
     */

    public function index( Request $request ){
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $deparments =   Departments::select('id', 'name')->get();
    
        return Inertia::render('Absenteeism/Index', [
            'branch_offices'    => $branchOffices,
            'deparments'        => $deparments,
        ]);
    }

    public function filter_data( Request $request ){
        
        $data = Absenteeism::index([
            'planta'       => $request->planta,
            'departamento' => $request->departamento,
            'empleados'    => $request->empleados,
            'semana'       => $request->semana,
            'anio'         => $request->anio,
            'tipo_falta'   => $request->tipo_falta,
        ]);
        $employees = Employee::select('id', 'full_name')->whereIn('status', ['entry', 'reentry'])
        ->where('branch_office_id', $request->planta)->get();

        $data = collect($data)->map(function ($item) use ($employees) {

            $date = Carbon::parse($item->date);

            if ($date->dayOfWeek === Carbon::SUNDAY) {
                $date->addDay();
            } else {
                $date->addDays(7);
            }

            $formattedDate = $date->format('d/m/Y');

            return [
                'employee_id'            => $item->employee_id ?? null,
                'type'                   => $item->external_code,
                'percentaje'             => 100,
                'document_imss'          => $item->document_number ?? null,
                'observation_date'       => Carbon::parse($item->date)->format('d/m/Y'),
                'date'                   => $formattedDate,
                'number_type_incapacity' => $item->incapacity_code
            ];
        });

        return response()->json([
            'data'      => $data->values(),
            'employees' => $employees
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
