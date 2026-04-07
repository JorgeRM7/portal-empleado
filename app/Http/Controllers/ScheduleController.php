<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Schedules;
use Illuminate\Validation\Rule;

class ScheduleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return Inertia::render('Schedule/Index', [
            'Schedules' => Schedules::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return Inertia::render('Schedule/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate(
            [
                // ===== GENERAL =====
                'name' => ['required', 'string', 'max:255', Rule::unique('schedules', 'name')],
                'entry_time' => ['required', 'date_format:H:i'],
                'leave_time' => ['required', 'date_format:H:i'],

                'normal_double_overtime' => ['nullable', 'numeric', 'min:0'],
                'normal_triple_overtime' => ['nullable', 'numeric', 'min:0'],

                // ===== TOLERANCIAS =====
                'tolerance_before_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_before_entry_type' => ['required', 'string'],

                'tolerance_after_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_after_entry_type' => ['required', 'string'],

                'tolerance_before_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_before_leave_type' => ['required', 'string'],

                'tolerance_after_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_after_leave_type' => ['required', 'string'],

                // ===== TIEMPO EXTRA =====
                'double_overtime' => ['nullable', 'numeric', 'min:0'],
                'double_overtime_hour_value' => ['nullable', 'numeric', 'min:0'],

                'triple_overtime' => ['nullable', 'numeric', 'min:0'],
                'triple_overtime_hour_value' => ['nullable', 'numeric', 'min:0'],

                // ===== TOLERANCIAS TIEMPO EXTRA =====
                'tolerance_overtime_before_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_before_entry_type' => ['required', 'string'],

                'tolerance_overtime_after_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_after_entry_type' => ['required', 'string'],

                'tolerance_overtime_before_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_before_leave_type' => ['required', 'string'],

                'tolerance_overtime_after_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_after_leave_type' => ['required', 'string'],
            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'date_format' => 'El campo :attribute debe tener el formato correcto (HH:MM).',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'name' => 'Nombre del horario',
                'entry_time' => 'Hora de entrada',
                'leave_time' => 'Hora de salida',

                'normal_double_overtime' => 'Horas extra dobles',
                'normal_triple_overtime' => 'Horas extra triples',

                'double_overtime' => 'Horas extra dobles',
                'double_overtime_hour_value' => 'Fórmula hora extra doble',
                'triple_overtime' => 'Horas extra triples',
                'triple_overtime_hour_value' => 'Fórmula hora extra triple',

                'tolerance_before_entry_time' => 'Tolerancia antes de la entrada',
                'tolerance_before_entry_type' => 'Tipo tolerancia antes de la entrada',
                'tolerance_after_entry_time' => 'Tolerancia después de la entrada',
                'tolerance_after_entry_type' => 'Tipo tolerancia después de la entrada',

                'tolerance_before_leave_time' => 'Tolerancia antes de la salida',
                'tolerance_before_leave_type' => 'Tipo tolerancia antes de la salida',
                'tolerance_after_leave_time' => 'Tolerancia después de la salida',
                'tolerance_after_leave_type' => 'Tipo tolerancia después de la salida',

                'tolerance_overtime_before_entry_time' => 'Tolerancia antes de la entrada (tiempo extra)',
                'tolerance_overtime_before_entry_type' => 'Tipo tolerancia antes de la entrada (tiempo extra)',
                'tolerance_overtime_after_entry_time' => 'Tolerancia después de la entrada (tiempo extra)',
                'tolerance_overtime_after_entry_type' => 'Tipo tolerancia después de la entrada (tiempo extra)',

                'tolerance_overtime_before_leave_time' => 'Tolerancia antes de la salida (tiempo extra)',
                'tolerance_overtime_before_leave_type' => 'Tipo tolerancia antes de la salida (tiempo extra)',
                'tolerance_overtime_after_leave_time' => 'Tolerancia después de la salida (tiempo extra)',
                'tolerance_overtime_after_leave_type' => 'Tipo tolerancia después de la salida (tiempo extra)',
            ]
        );

        $validated['normal_double_overtime'] = $validated['normal_double_overtime'] ?? 0;
        $validated['normal_triple_overtime'] = $validated['normal_triple_overtime'] ?? 0;
        $validated['double_overtime'] = $validated['double_overtime'] ?? 0;
        $validated['double_overtime_hour_value'] = $validated['double_overtime_hour_value'] ?? 0;
        $validated['triple_overtime'] = $validated['triple_overtime'] ?? 0;
        $validated['triple_overtime_hour_value'] = $validated['triple_overtime_hour_value'] ?? 0;

        try {

            Schedules::create($validated);

            return redirect()
                ->route('schedules.index')
                ->with('success', 'Horario creado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear el horario.');
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        return Inertia::render('Schedule/Show',[
            'Schedules' => Schedules::findOrFail($id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        return Inertia::render('Schedule/Edit',[
            'Schedules' => Schedules::findOrFail($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedules $schedule)
    {

        $validated = $request->validate(
            [
                // ===== GENERAL =====
                'name' => ['required', 'string', 'max:255', Rule::unique('schedules', 'name')->ignore($schedule->id)],
                'entry_time' => ['required', 'date_format:H:i'],
                'leave_time' => ['required', 'date_format:H:i'],

                'normal_double_overtime' => ['nullable', 'numeric', 'min:0'],
                'normal_triple_overtime' => ['nullable', 'numeric', 'min:0'],

                // ===== TOLERANCIAS =====
                'tolerance_before_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_before_entry_type' => ['required', 'string'],

                'tolerance_after_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_after_entry_type' => ['required', 'string'],

                'tolerance_before_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_before_leave_type' => ['required', 'string'],

                'tolerance_after_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_after_leave_type' => ['required', 'string'],

                // ===== TIEMPO EXTRA =====
                'double_overtime' => ['nullable', 'numeric', 'min:0'],
                'double_overtime_hour_value' => ['nullable', 'numeric', 'min:0'],

                'triple_overtime' => ['nullable', 'numeric', 'min:0'],
                'triple_overtime_hour_value' => ['nullable', 'numeric', 'min:0'],

                // ===== TOLERANCIAS TIEMPO EXTRA =====
                'tolerance_overtime_before_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_before_entry_type' => ['required', 'string'],

                'tolerance_overtime_after_entry_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_after_entry_type' => ['required', 'string'],

                'tolerance_overtime_before_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_before_leave_type' => ['required', 'string'],

                'tolerance_overtime_after_leave_time' => ['required', 'integer', 'min:0'],
                'tolerance_overtime_after_leave_type' => ['required', 'string'],
            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'date_format' => 'El campo :attribute debe tener el formato correcto (HH:MM).',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'name' => 'Nombre del horario',
                'entry_time' => 'Hora de entrada',
                'leave_time' => 'Hora de salida',

                'normal_double_overtime' => 'Horas extra dobles',
                'normal_triple_overtime' => 'Horas extra triples',

                'double_overtime' => 'Horas extra dobles',
                'double_overtime_hour_value' => 'Fórmula hora extra doble',
                'triple_overtime' => 'Horas extra triples',
                'triple_overtime_hour_value' => 'Fórmula hora extra triple',

                'tolerance_before_entry_time' => 'Tolerancia antes de la entrada',
                'tolerance_before_entry_type' => 'Tipo tolerancia antes de la entrada',
                'tolerance_after_entry_time' => 'Tolerancia después de la entrada',
                'tolerance_after_entry_type' => 'Tipo tolerancia después de la entrada',

                'tolerance_before_leave_time' => 'Tolerancia antes de la salida',
                'tolerance_before_leave_type' => 'Tipo tolerancia antes de la salida',
                'tolerance_after_leave_time' => 'Tolerancia después de la salida',
                'tolerance_after_leave_type' => 'Tipo tolerancia después de la salida',

                'tolerance_overtime_before_entry_time' => 'Tolerancia antes de la entrada (tiempo extra)',
                'tolerance_overtime_before_entry_type' => 'Tipo tolerancia antes de la entrada (tiempo extra)',
                'tolerance_overtime_after_entry_time' => 'Tolerancia después de la entrada (tiempo extra)',
                'tolerance_overtime_after_entry_type' => 'Tipo tolerancia después de la entrada (tiempo extra)',

                'tolerance_overtime_before_leave_time' => 'Tolerancia antes de la salida (tiempo extra)',
                'tolerance_overtime_before_leave_type' => 'Tipo tolerancia antes de la salida (tiempo extra)',
                'tolerance_overtime_after_leave_time' => 'Tolerancia después de la salida (tiempo extra)',
                'tolerance_overtime_after_leave_type' => 'Tipo tolerancia después de la salida (tiempo extra)',
            ]
        );

        $validated['normal_double_overtime'] = $validated['normal_double_overtime'] ?? 0;
        $validated['normal_triple_overtime'] = $validated['normal_triple_overtime'] ?? 0;
        $validated['double_overtime'] = $validated['double_overtime'] ?? 0;
        $validated['double_overtime_hour_value'] = $validated['double_overtime_hour_value'] ?? 0;
        $validated['triple_overtime'] = $validated['triple_overtime'] ?? 0;
        $validated['triple_overtime_hour_value'] = $validated['triple_overtime_hour_value'] ?? 0;

        try {

            $schedule->update($validated);

            return redirect()
                ->route('schedules.index')
                ->with('success', 'Horario actualizado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar el horario.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedules $schedule)
    {
        
        try {

            $schedule->delete();

            return redirect()
                ->back()
                ->with('success', 'Horario eliminado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al eliminar el horario.');
        }
    }

    public function destroyMultiple(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:schedules,id',
        ]);

        Schedules::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Registros eliminados');
    }

}
