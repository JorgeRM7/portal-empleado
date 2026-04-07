<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\EmployeeOvertimeEstimate;
use App\Models\Logs;
use App\Models\Motivo;
use App\Models\Position;
use App\Models\Schedules;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeOvertimeEstimateController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $estimates = EmployeeOvertimeEstimate::index($request->branch_office_id, $request->position, $request->week, $request->status);
        return $estimates;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branchOffices = BranchOffice::query()->select('id', 'name', 'code')->where('active', '=', 1)->get();
        $positions = Position::select('id', 'name')->get();
        $motivos = Motivo::all();
        $view = 'estimate';
        $schedules = Schedules::select('id', 'name')->get();
        return Inertia::render('EmployeeOvertimes/Create', [
            'branchOffices' => $branchOffices,
            'positions' => $positions,
            'motivos' => $motivos,
            'view' => $view,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->complete_turn == true){
            $validated = $request->validate([
                'branch_office_id' => 'required',
                'week' => 'required',
                'position_id' => 'required',
                'number_employees' => 'required',
                'complete_turn' => 'required',
                'schedule_id' => 'required',
                'overtime' => 'required',
                'current_turn' => 'required',
                'double_overtime' => 'required',
                'triple_overtime' => 'required',
                'motivo' => 'required',
                'coment' => 'nullable',
            ],[
                'branch_office_id.required' => 'La planta es requerida',
                'week.required' => 'La semana es requerida',
                'position_id.required' => 'El puesto es requerido',
                'number_employees.required' => 'El número de empleados es requerido',
                'complete_turn.required' => 'El turno completo es requerido',
                'schedule_id.required' => 'El turno es requerido',
                'overtime.required' => 'Las horas extra son requeridas',
                'current_turn.required' => 'El turno actual es requerido',
                'double_overtime.required' => 'Las horas extra dobles son requeridas',
                'triple_overtime.required' => 'Las horas extra triples son requeridas',
                'motivo.required' => 'El motivo es requerido',
                'coment.nullable' => 'El comentario es opcional',
            ]);
        }else{
            $validated = $request->validate([
                'branch_office_id' => 'required',
                'week' => 'required',
                'position_id' => 'required',
                'number_employees' => 'required',
                'complete_turn' => 'required',
                'schedule_id' => 'nullable',
                'current_turn' => 'required',
                'overtime' => 'required',
                'double_overtime' => 'required',
                'triple_overtime' => 'required',
                'motivo' => 'required',
                'coment' => 'nullable',
            ],[
                'branch_office_id.required' => 'La planta es requerida',
                'week.required' => 'La semana es requerida',
                'position_id.required' => 'El puesto es requerido',
                'number_employees.required' => 'El número de empleados es requerido',
                'complete_turn.required' => 'El turno completo es requerido',
                'schedule_id.nullable' => 'El turno es opcional',
                'overtime.required' => 'Las horas extra son requeridas',
                'current_turn.required' => 'El turno actual es requerido',
                'double_overtime.required' => 'Las horas extra dobles son requeridas',
                'triple_overtime.required' => 'Las horas extra triples son requeridas',
                'motivo.required' => 'El motivo es requerido',
                'coment.nullable' => 'El comentario es opcional',
            ]);
        }

        $overtimesEstimate = EmployeeOvertimeEstimate::create($validated);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes_estimate',
            'date' => Carbon::now(),
            'relationship_id' => $overtimesEstimate->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeOvertimeEstimate $employeeOvertimeEstimate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeOvertimeEstimate $estimate, Request $request)
    {
        $branchOffices = BranchOffice::all();
        $positions = Position::all();
        $motivos = Motivo::all();
        $view = 'estimate';
        $schedules = Schedules::all();

        return Inertia::render('EmployeeOvertimes/Edit', [
            'branchOffices' => $branchOffices,
            'positions' => $positions,
            'motivos' => $motivos,
            'view' => $view,
            'schedules' => $schedules,
            'employeeOvertimeEstimate' => $estimate,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeOvertimeEstimate $estimate)
    {
        if($request->complete_turn == true){
            $validated = $request->validate([
                'branch_office_id' => 'required',
                'week' => 'required',
                'position_id' => 'required',
                'number_employees' => 'required',
                'complete_turn' => 'required',
                'schedule_id' => 'required',
                'overtime' => 'required',
                'current_turn' => 'required',
                'double_overtime' => 'required',
                'triple_overtime' => 'required',
                'motivo' => 'required',
                'comment' => 'nullable',
            ],[
                'branch_office_id.required' => 'La planta es requerida',
                'week.required' => 'La semana es requerida',
                'position_id.required' => 'El puesto es requerido',
                'number_employees.required' => 'El número de empleados es requerido',
                'complete_turn.required' => 'El turno completo es requerido',
                'schedule_id.required' => 'El turno es requerido',
                'overtime.required' => 'Las horas extra son requeridas',
                'current_turn.required' => 'El turno actual es requerido',
                'double_overtime.required' => 'Las horas extra dobles son requeridas',
                'triple_overtime.required' => 'Las horas extra triples son requeridas',
                'motivo.required' => 'El motivo es requerido',
                'comment.nullable' => 'El comentario es opcional',
            ]);
        }else{
            $validated = $request->validate([
                'branch_office_id' => 'required',
                'week' => 'required',
                'position_id' => 'required',
                'number_employees' => 'required',
                'complete_turn' => 'required',
                'schedule_id' => 'nullable',
                'current_turn' => 'required',
                'overtime' => 'required',
                'double_overtime' => 'required',
                'triple_overtime' => 'required',
                'motivo' => 'required',
                'comment' => 'nullable',
            ],[
                'branch_office_id.required' => 'La planta es requerida',
                'week.required' => 'La semana es requerida',
                'position_id.required' => 'El puesto es requerido',
                'number_employees.required' => 'El número de empleados es requerido',
                'complete_turn.required' => 'El turno completo es requerido',
                'schedule_id.nullable' => 'El turno es opcional',
                'overtime.required' => 'Las horas extra son requeridas',
                'current_turn.required' => 'El turno actual es requerido',
                'double_overtime.required' => 'Las horas extra dobles son requeridas',
                'triple_overtime.required' => 'Las horas extra triples son requeridas',
                'motivo.required' => 'El motivo es requerido',
                'comment.nullable' => 'El comentario es opcional',
            ]);
        }

        $oldData = $estimate->getOriginal();

        $estimate->update($validated);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old-date' => json_encode($oldData),
            'table_name' => 'employee_overtimes_estimate',
            'date' => Carbon::now(),
            'relationship_id' => $estimate->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeOvertimeEstimate $estimate)
    {
        $estimate->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes_estimate',
            'date' => Carbon::now(),
            'relationship_id' => $estimate->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    public function generatePdf(Request $request)
    {
        //dd($request->all());
        $headers = $request->input('headers');
        $rows = $request->input('rows');
        $name_branch_office = $request->input('name_branch_office');

        // Generamos el PDF usando una vista blade. 
        // Lo ponemos en 'landscape' (horizontal) porque las tablas suelen ser anchas.
        $pdf = Pdf::loadView('pdf.estimaciones', [
            'headers' => $headers,
            'rows' => $rows,
            'name_branch_office' => $name_branch_office
        ])->setPaper('letter', 'landscape');

        return $pdf->download('estimaciones.pdf');
    }

    public function approve(Request $request)
    {
        $employee_overtime_estimate = EmployeeOvertimeEstimate::find($request->id);
        $employee_overtime_estimate->approved_at = Carbon::now();
        $employee_overtime_estimate->approved_by = Auth::user()->id;
        $employee_overtime_estimate->save();

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes_estimate',
            'date' => Carbon::now(),
            'relationship_id' => $employee_overtime_estimate->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    public function decline(Request $request)
    {
        $employee_overtime_estimate = EmployeeOvertimeEstimate::find($request->id);
        $employee_overtime_estimate->declined_at = Carbon::now();
        $employee_overtime_estimate->declined_by = Auth::user()->id;
        $employee_overtime_estimate->save();

        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes_estimate',
            'date' => Carbon::now(),
            'relationship_id' => $employee_overtime_estimate->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    public function multiApprove(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertimeEstimate::whereIn('id', $ids)->update([
            'approved_at' => Carbon::now(),
            'approved_by' => Auth::user()->id,
        ]);

        $records = EmployeeOvertimeEstimate::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes_estimate',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    public function multiDecline(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertimeEstimate::whereIn('id', $ids)->update([
            'declined_at' => Carbon::now(),
            'declined_by' => Auth::user()->id,
        ]);

        $records = EmployeeOvertimeEstimate::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DECLINED',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes_estimate',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertimeEstimate::whereIn('id', $ids)->delete();

        $records = EmployeeOvertimeEstimate::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DELETE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes_estimate',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "0"]);
    }
    
}
