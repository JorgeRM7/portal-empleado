<?php

namespace App\Http\Controllers;

use App\Models\AssistenceDaily;
use App\Models\Employee;
use App\Models\Incidence;
use App\Models\Schedules;
use App\Models\ShiftRole;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AssistencesDailysController
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
        $incidences = Incidence::select('id', 'name')->get();
        $schedules = ShiftRole::select('id', 'name')->get();

        return Inertia::render('AssistencesDaily/Index', [
            "branchOffices" => $branchOffices,
            "incidences" => $incidences,
            "schedules" => $schedules,
        ]);
    }

    public function filter_data( Request $request ){
        $data = AssistenceDaily::index([  
            'date_start'         => $request->date_start,
            'date_end'           => $request->date_end,
            'branch_office_id'   => $request->branch_office_id,
            'employees'          => $request->employees,
            'schedules'          => $request->schedules,
            'incidences'         => $request->incidences,
        ]);
        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry','change'])
            ->where('branch_office_id', $request->branch_office_id)
            ->get();

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
        return Inertia::render('AssistencesDaily/Create', [
            // 'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     */
    public function show( AssistenceDaily $event)
    {
        return Inertia::render('AssistencesDaily/Show', [
            'event' => $event
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit( AssistenceDaily $event )
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AssistenceDaily $event){
        
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $recordID )
    {
        $record= AssistenceDaily::find($recordID);

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
