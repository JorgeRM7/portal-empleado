<?php

namespace App\Http\Controllers;

use App\Models\AssistenceOverTimes;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Hikcentral;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AssistenceOverTimeController
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

        $deparments = Departments::select('id', 'name')->get();   

        return Inertia::render('AssistencesOvertimes/Index', [
            "branchOffices" => $branchOffices,
            "deparments" => $deparments
        ]);
    }

    public function filter_data( Request $request ){
        $data = AssistenceOverTimes::index([
                'branch_office_id'  => $request->branch_office_id,
                'dateStart'         => $request->date_start,
                'dateEnd'           => $request->date_end,
                'employees'         => $request->employees,
                'deparments'        => $request->deparments,
            ]
        );
        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->where('branch_office_id', $request->branch_office_id)
            ->get();

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
            $fecha = \Carbon\Carbon::parse($row->access_date);

            $row->dia_mes = $fecha->day;
            $row->dia_es  = $daysEs[$fecha->dayOfWeekIso];

            return $row;
        });

        

        return response()->json([
            'rows'  => $data,
            'employees' => $employees,
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
    public function destroy( $recordID )
    {
        $record= Hikcentral::find($recordID);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente',
            'registro' => $record
        ]);
    }
}
