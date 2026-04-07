<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\EarningDeduction;
use App\Models\PayrollEarningsDeduction;
use Illuminate\Support\Facades\Log;

class PayrollEarningsDeductionsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plantas = Auth::user()
            ->branchOffices()
            ->select('branch_offices.id', 'branch_offices.code', 'branch_offices.name')
            ->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        $persepcionesDeducciones = EarningDeduction::select("id", "name")->get();

        return Inertia::render('PayrollEarningsDeductions/Index', [
            'Plantas' => $plantas,
            'Empleados' => $employees,
            'PersepcionesDeducciones' => $persepcionesDeducciones,
        ]);
    }

    public function filterData(Request $request)
    {

        $weekNumber = null;
        $year   = null;

        if ($request->week) {

            [$year, $weekNumber] = explode('-W', $request->week);

            if($weekNumber < 10){
                $weekNumber = ltrim($weekNumber, "0");
            }

        }

        $rows = PayrollEarningsDeduction::indexPYD(
            $request->branch_office_id,
            $request->employee_id,
            $weekNumber,
            $year,
            $request->salary_payment_id
        );

        return $rows;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        $persepcionesDeducciones = EarningDeduction::select("id", "name", "type")->get();
        
        return Inertia::render('PayrollEarningsDeductions/Create', [
            'Empleados' => $employees,
            'PersepcionesDeducciones' => $persepcionesDeducciones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate(
            [
                'employee' => ['required'],
                'salary_payment' => ['required'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],

            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'employee' => 'Empleado',
                'salary_payment' => 'Percepción o Deducción',
                'start_date' => 'Fecha de inicio',
                'end_date' => 'Fecha final',
            ]
        );

        try {

            // Obtener datos del payload
            $employeeId = $validated['employee']['id'];
            $branchOfficeId = $validated['employee']['branch_office_id'];

            $salaryPaymentId = $validated['salary_payment']['id'];
            $type = $validated['salary_payment']['type'];

            // Fechas con Carbon
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $weekNumber = $startDate->weekOfYear;
            $weekYear = $startDate->year;

            PayrollEarningsDeduction::create([
                'employee_id' => $employeeId,
                'salary_payment_id' => $salaryPaymentId,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'week_number' => $weekNumber,
                'week_year' => $weekYear,
                'branch_office_id' => $branchOfficeId,
            ]);

            return redirect()
                ->route('payroll-earnings-deductions.index')
                ->with('success','Registro creado correctamente.');


        } catch (\Throwable $e) {

            Log::error('Error al crear el registro', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear el registro.');
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        $persepcionesDeducciones = EarningDeduction::select("id", "name", "type")->get();
        
        return Inertia::render('PayrollEarningsDeductions/Show', [
            'PerDed' => PayrollEarningsDeduction::findOrFail($id),
            'Empleados' => $employees,
            'PersepcionesDeducciones' => $persepcionesDeducciones,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        $persepcionesDeducciones = EarningDeduction::select("id", "name", "type")->get();
        
        return Inertia::render('PayrollEarningsDeductions/Edit', [
            'PerDed' => PayrollEarningsDeduction::findOrFail($id),
            'Empleados' => $employees,
            'PersepcionesDeducciones' => $persepcionesDeducciones,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayrollEarningsDeduction $payrollEarningsDeduction)
    {
        
        $validated = $request->validate(
            [
                'employee' => ['required'],
                'salary_payment' => ['required'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],

            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'employee' => 'Empleado',
                'salary_payment' => 'Percepción o Deducción',
                'start_date' => 'Fecha de inicio',
                'end_date' => 'Fecha final',
            ]
        );

        try {

            // Obtener datos del payload
            $employeeId = $validated['employee']['id'];
            $branchOfficeId = $validated['employee']['branch_office_id'];

            $salaryPaymentId = $validated['salary_payment']['id'];
            $type = $validated['salary_payment']['type'];

            // Fechas con Carbon
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $weekNumber = $startDate->weekOfYear;
            $weekYear = $startDate->year;

            $payrollEarningsDeduction->update([
                'employee_id' => $employeeId,
                'salary_payment_id' => $salaryPaymentId,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'week_number' => $weekNumber,
                'week_year' => $weekYear,
                'branch_office_id' => $branchOfficeId,
            ]);

            return redirect()
                ->route('payroll-earnings-deductions.index')
                ->with('success','Registro actualizado correctamente.');


        } catch (\Throwable $e) {

            Log::error('Error al actualizar el registro', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar el registro.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PayrollEarningsDeduction $payrollEarningsDeduction)
    {
        $payrollEarningsDeduction->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:employee_salary_payments,id',
        ]);

        PayrollEarningsDeduction::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Registros eliminados');
    }
}
