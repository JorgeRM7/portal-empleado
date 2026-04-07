<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClassificationsController
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classifications = Classification::index();
        $branchOffice = Auth::user()
            ->branchOffices()
            ->select('branch_offices.id', 'branch_offices.code')
            ->get();

        return Inertia::render('Classifications/Index', [
            'Classifications' => $classifications,
            'BranchOffices' => $branchOffice
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
        
        $validated = $request->validate(
            [
                'branch_office_id' => ['required'],
                'classification' => ['required', 'integer', 'min:0', Rule::unique('classifications', 'id')],
                'description' => ['nullable', 'string', 'max:1000'],
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
                'branch_office_id' => 'Planta',
                'classification' => 'Clasificación',
                'description' => 'Descripción',
            ]
        );

        try {

            Classification::create([
                'id' => $request['classification'],
                'branch_office_id' => $request['branch_office_id'],
                'classification' => $request['classification'],
                'description' => $request['description'],
            ]);

            return redirect()
                ->route('classifications.index')
                ->with('success','Registro creado correctamente.');


        } catch (\Throwable $e) {

            Log::error('Error al guardar el registro', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al guardar el registro.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate(
            [
                'branch_office_id' => ['required', 'integer'],
                'classification'   => [
                    'required',
                    'integer',
                    'min:0',
                    Rule::unique('classifications', 'id')->ignore($id)
                ],
                'description'      => ['nullable', 'string', 'max:1000'],
            ],
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique'   => 'El campo :attribute ya está registrado.',
            ],
            [
                'branch_office_id' => 'Planta',
                'classification'   => 'Clasificación',
                'description'      => 'Descripción',
            ]
        );

        try {

            $classification = Classification::findOrFail($id);

            $classification->id = $validated['classification'];
            $classification->classification = $validated['classification'];
            $classification->branch_office_id = $validated['branch_office_id'];
            $classification->description = $validated['description'] ?? null;

            $classification->save();

            return redirect()
                ->route('classifications.index')
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
    public function destroy(Classification $classification)
    {
        $classification->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
