<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Employee;
use Inertia\Inertia;
use Illuminate\Http\Request;

class EventController
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

        return Inertia::render('Events/Index', [
            "branchOffices" => $branchOffices,
        ]);
    }

    public function filter_data( Request $request ){
        $data = Event::index([  
            'date_start'            => $request->date_start,
            'date_end'              => $request->date_end,
            'holiday'               => $request->holiday,
            'selectedBranchOfficeId'=> $request->selectedBranchOfficeId,
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
        return Inertia::render('Events/Create', [
            // 'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:255',
            'all_day' => 'nullable|boolean',
            'holiday' => 'nullable|boolean',
            'employee_id' => 'nullable|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'only_branch_office' => 'nullable|boolean',
            'branch_office_id' => 'nullable|exists:branch_offices,id',
            'birthday' => 'nullable|boolean'
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#3b82f6',
            'all_day' => $validated['all_day'] ?? false,
            'holiday' => $validated['holiday'] ?? false,
            'employee_id' => $validated['employee_id'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
            'only_branch_office' => $validated['only_branch_office'] ?? false,
            'branch_office_id' => $validated['branch_office_id'] ?? null,
            'birthday' => $validated['birthday'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Evento creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show( Event $event)
    {
        return Inertia::render('Events/Show', [
            'event' => $event
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit( Event $event )
    {
        return Inertia::render('Events/Edit', [
            'event' => $event
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event){
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'all_day' => 'boolean',
            'holiday' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $event->update($validated);

        return redirect()->back()->with('success', 'Evento actualizado correctamente');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $recordID )
    {
        $record= Event::find($recordID);

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
