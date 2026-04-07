<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Logs;
use App\Models\StateHistory;
use App\Models\StatusReason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StateHistoryController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::select('id','full_name', 'profile_photo_path', 'branch_office_id')->where('status', '!=', 'termination')->get();
        $status_reasons = StatusReason::select('id', 'name')->get();
        return Inertia::render('StateHistory/Index', [
            'employees' => $employees,
            'statusReason' => $status_reasons
        ]);
    }

    public function getData(Request $request){
        return StateHistory::getData($request->id);
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
        $validated = $request->validate([
            'status' => 'required',
            'content' => 'nullable',
            'date' => 'required',
            'reason_id' => 'required',
            'employee_id' => 'required'
        ], [
            'status.required' => 'El campo de tipo de estado es requerido',
            'date.required' => 'El campo fecha es requerido',
            'reason_id.required' => 'El campo de razon es requerido',
        ]);

        $state = StateHistory::create($validated);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_statuses',
            'date' => Carbon::now(),
            'relationship_id' => $state->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StateHistory $stateHistory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StateHistory $stateHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StateHistory $stateHistory)
    {
        $validated = $request->validate([
            'status' => 'required',
            'content' => 'nullable',
            'date' => 'required',
            'reason_id' => 'required',
            'employee_id' => 'required'
        ], [
            'status.required' => 'El campo de tipo de estado es requerido',
            'date.required' => 'El campo fecha es requerido',
            'reason_id.required' => 'El campo de razon es requerido',
        ]);

        $stateHistory->update($validated);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_statuses',
            'date' => Carbon::now(),
            'relationship_id' => $stateHistory->id
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StateHistory $stateHistory)
    {
        $stateHistory->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_statuses',
            'date' => Carbon::now(),
            'relationship_id' => $stateHistory->id
        ]);
    }
}
