<?php

namespace App\Http\Controllers;

use App\Models\EmployeePolicy;
use App\Models\BenefitCategoryIncidence;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeePolicyController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return Inertia::render('EmployeePolicies/Index', [
            'EmployeePolicy' => EmployeePolicy::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return Inertia::render('EmployeePolicies/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            // ------------------ DATOS GENERALES ------------------

            'name' => ['required', 'string', 'max:255'],

            'week_work_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:8'
            ],

            'vacation_bonus_year' => [
                'nullable',
                'boolean'
            ],

            'absences_discount' => [
                'nullable',
                'boolean'
            ],

            'incidences_discount' => [
                'nullable',
                'boolean'
            ],

            'vacation_bonus' => [
                'nullable',
                'numeric',
                'between:0,1'
            ],

            // ------------------ VACACIONES ------------------

            'vacations' => [
                'required',
                'array',
                'min:1'
            ],

            'vacations.*.days' => [
                'required',
                'integer',
                'min:0',
                'max:100'
            ],

            'vacations.*.years' => [
                'required',
                'integer',
                'min:0',
                'max:50'
            ],

        ], [

            // ------------------ MENSAJES ------------------

            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',

            'week_work_days.integer' => 'Los días laborales deben ser un número entero.',
            'week_work_days.min' => 'Los días laborales no pueden ser menores a 0.',
            'week_work_days.max' => 'Los días laborales no pueden ser mayores a 8.',

            'vacation_bonus.numeric' => 'La prima vacacional debe ser un número válido.',
            'vacation_bonus.between' => 'La prima vacacional debe estar entre 0 y 1.',

            'vacations.required' => 'Debe agregar al menos una regla de vacaciones.',
            'vacations.array' => 'Las reglas de vacaciones no tienen formato válido.',
            'vacations.min' => 'Debe existir al menos una regla de vacaciones.',

            'vacations.*.days.required' =>
                'La cantidad de días es obligatoria.',
            'vacations.*.days.integer' =>
                'La cantidad de días debe ser un número entero.',
            'vacations.*.days.min' =>
                'La cantidad de días no puede ser menor a 0.',
            'vacations.*.days.max' =>
                'La cantidad de días no puede ser mayor a 100.',

            'vacations.*.years.required' =>
                'La antigüedad es obligatoria.',
            'vacations.*.years.integer' =>
                'La antigüedad debe ser un número entero.',
            'vacations.*.years.min' =>
                'La antigüedad no puede ser negativa.',
        ]);

        DB::beginTransaction();

        try {

            // ------------------ CREAR BENEFIT ------------------
            EmployeePolicy::create([
                'name'                     => $validated['name'],
                'vacations'                => $validated['vacations'] ?? null,
                'week_work_days'           => $validated['week_work_days'] ?? 6,
                'vacation_bonus_year'      => $validated['vacation_bonus_year'] ?? 0,
                'absences_discount'        => $validated['absences_discount'] ?? 0,
                'incidences_discount'      => $validated['incidences_discount'] ?? 0,
                'vacation_bonus'           => $validated['vacation_bonus'],
                'payment_days'             => 0,
                'seventh_day_discount'     => 0,
            ]);

            DB::commit();

            return redirect()
                ->route('policies.index')
                ->with('success', 'Regla creada correctamente');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al crear la regla', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la regla.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        return Inertia::render('EmployeePolicies/Show', [
            'EmployeePolicy' => EmployeePolicy::findOrFail($id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('EmployeePolicies/Edit', [
            'EmployeePolicy' => EmployeePolicy::findOrFail($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeePolicy $policy)
    {

        $validated = $request->validate([
            // ------------------ DATOS GENERALES ------------------

            'name' => ['required', 'string', 'max:255'],

            'week_work_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:8'
            ],

            'vacation_bonus_year' => [
                'nullable',
                'boolean'
            ],

            'absences_discount' => [
                'nullable',
                'boolean'
            ],

            'incidences_discount' => [
                'nullable',
                'boolean'
            ],

            'vacation_bonus' => [
                'nullable',
                'numeric',
                'between:0,1'
            ],

            // ------------------ VACACIONES ------------------

            'vacations' => [
                'required',
                'array',
                'min:1'
            ],

            'vacations.*.days' => [
                'required',
                'integer',
                'min:0',
                'max:100'
            ],

            'vacations.*.years' => [
                'required',
                'integer',
                'min:0',
                'max:50'
            ],

        ], [

            // ------------------ MENSAJES ------------------

            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',

            'week_work_days.integer' => 'Los días laborales deben ser un número entero.',
            'week_work_days.min' => 'Los días laborales no pueden ser menores a 0.',
            'week_work_days.max' => 'Los días laborales no pueden ser mayores a 8.',

            'vacation_bonus.numeric' => 'La prima vacacional debe ser un número válido.',
            'vacation_bonus.between' => 'La prima vacacional debe estar entre 0 y 1.',

            'vacations.required' => 'Debe agregar al menos una regla de vacaciones.',
            'vacations.array' => 'Las reglas de vacaciones no tienen formato válido.',
            'vacations.min' => 'Debe existir al menos una regla de vacaciones.',

            'vacations.*.days.required' =>
                'La cantidad de días es obligatoria.',
            'vacations.*.days.integer' =>
                'La cantidad de días debe ser un número entero.',
            'vacations.*.days.min' =>
                'La cantidad de días no puede ser menor a 0.',
            'vacations.*.days.max' =>
                'La cantidad de días no puede ser mayor a 100.',

            'vacations.*.years.required' =>
                'La antigüedad es obligatoria.',
            'vacations.*.years.integer' =>
                'La antigüedad debe ser un número entero.',
            'vacations.*.years.min' =>
                'La antigüedad no puede ser negativa.',
        ]);

        DB::beginTransaction();

        try {

            // ------------------ CREAR BENEFIT ------------------
            $policy->update([
                'name'                     => $validated['name'],
                'vacations'                => $validated['vacations'] ?? null,
                'week_work_days'           => $validated['week_work_days'] ?? 6,
                'vacation_bonus_year'      => $validated['vacation_bonus_year'] ?? 0,
                'absences_discount'        => $validated['absences_discount'] ?? 0,
                'incidences_discount'      => $validated['incidences_discount'] ?? 0,
                'vacation_bonus'           => $validated['vacation_bonus'],
                'payment_days'             => 0,
                'seventh_day_discount'     => 0,
            ]);

            DB::commit();

            return redirect()
                ->route('policies.index')
                ->with('success', 'Regla actualizada correctamente');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al crear la regla', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar la regla.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeePolicy $policy)
    {

        $policy->delete();
        
        return redirect()
            ->back()
            ->with('success', 'Registro eliminado exitosamente.');
    }
}
