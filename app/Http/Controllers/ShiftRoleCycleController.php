<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Logs;
use App\Models\Schedules;
use App\Models\ShiftRole;
use App\Models\ShiftRoleCycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftRoleCycleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::select('id', 'full_name', 'branch_office_id')->where('status', '!=', 'termination')->get();
        return Inertia::render('ShiftRoleCycles/Index', [
            'employees' => $employees,
        ]);
            
    }

    public function getShiftRoleCycles(Request $request)
    {
        return ShiftRoleCycle::getShiftRoleCycles($request->employee_id, $request->end_date, $request->eliminated, $request->branch_office);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $schedules = Schedules::all();
        $shifts = ShiftRole::all();
        
        
        return Inertia::render('ShiftRoleCycles/Create', [
            'employees' => $employees,
            'schedules' => $schedules,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!$request->not_end_date){
            $validated = $request->validate([
                'employee_id' => 'required',
                'schedule_id' => 'required',
                'shift_role_id' => 'required',
                'started_at' => 'required',
                'ends_at' => 'required',
                'not_end_date' => 'required',
            ], [
                'employee_id.required' => 'El empleado es requerido',
                'schedule_id.required' => 'El horario es requerido',
                'shift_role_id.required' => 'El turno es requerido',
                'started_at.required' => 'La fecha de inicio es requerida',
                'ends_at.required' => 'La fecha de fin es requerida',
                'not_end_date.required' => 'La fecha de fin es requerida',
            ]);
        }else{
            $validated = $request->validate([
                'employee_id' => 'required',
                'schedule_id' => 'required',
                'shift_role_id' => 'required',
                'started_at' => 'required',
                'ends_at' => 'nullable',
            ], [
                'employee_id.required' => 'El empleado es requerido',
                'schedule_id.required' => 'El horario es requerido',
                'shift_role_id.required' => 'El turno es requerido',
                'started_at.required' => 'La fecha de inicio es requerida',
            ]);
        }

        $shiftCycle = ShiftRoleCycle::create($validated);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_shift_role_cycles',
            'date' => Carbon::now(),
            'relationship_id' => $shiftCycle->id
        ]);

        return redirect()->route('shift-role-cycles');
    }

    /**
     * Display the specified resource.
     */
    public function show(ShiftRoleCycle $shiftRoleCycle)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftRoleCycle $shiftRoleCycle)
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $schedules = Schedules::all();
        $shifts = ShiftRole::all();
        
        return Inertia::render('ShiftRoleCycles/Edit', [
            'shiftRoleCycle' => $shiftRoleCycle,
            'employees' => $employees,
            'schedules' => $schedules,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShiftRoleCycle $shiftRoleCycle)
    {
        if(!$request->not_end_date){
            $validated = $request->validate([
                'employee_id' => 'required',
                'schedule_id' => 'required',
                'shift_role_id' => 'required',
                'started_at' => 'required',
                'ends_at' => 'required',
                'not_end_date' => 'required',
            ], [
                'employee_id.required' => 'El empleado es requerido',
                'schedule_id.required' => 'El horario es requerido',
                'shift_role_id.required' => 'El turno es requerido',
                'started_at.required' => 'La fecha de inicio es requerida',
                'ends_at.required' => 'La fecha de fin es requerida',
                'not_end_date.required' => 'La fecha de fin es requerida',
            ]);
        }else{
            $validated = $request->validate([
                'employee_id' => 'required',
                'schedule_id' => 'required',
                'shift_role_id' => 'required',
                'started_at' => 'required',
                'ends_at' => 'nullable',
            ], [
                'employee_id.required' => 'El empleado es requerido',
                'schedule_id.required' => 'El horario es requerido',
                'shift_role_id.required' => 'El turno es requerido',
                'started_at.required' => 'La fecha de inicio es requerida',
            ]);
        }

        $oldData = $shiftRoleCycle->getOriginal();

        $shiftRoleCycle->update($validated);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old_data' => json_encode($oldData),
            'table_name' => 'employee_shift_role_cycles',
            'date' => Carbon::now(),
            'relationship_id' => $shiftRoleCycle->id
        ]);

        return redirect()->route('shift-role-cycles');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftRoleCycle $shiftRoleCycle)
    {
        $shiftRoleCycle->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_shift_role_cycles',
            'date' => Carbon::now(),
            'relationship_id' => $shiftRoleCycle->id
        ]);

        return redirect()->route('shift-role-cycles');
    }

    public function destroyMultiple(Request $request)
    {
        $shiftRoleCycles = ShiftRoleCycle::whereIn('id', $request->params['ids'])->get();
        foreach ($shiftRoleCycles as $shiftRoleCycle) {
            $shiftRoleCycle->delete();
            Logs::create([
                'action' => 'DELETE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_shift_role_cycles',
                'date' => Carbon::now(),
                'relationship_id' => $shiftRoleCycle->id
            ]);
        }

        return redirect()->route('shift-role-cycles');
    }
}
