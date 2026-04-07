<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CompanyController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::get();

        return Inertia::render('Companies/Index', [
            'Companies' => $companies,
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
        $validated = $request->validate([
            // ------------------ COMPANY ------------------
            'name' => ['required', 'string', 'max:255', 
                Rule::unique('companies', 'name')],
            'code' => ['required', 'string', 'max:255', 
                Rule::unique('companies', 'code')],
            'description' => ['max:255'],
        ],
        [
            // ---------------- COMPANY ----------------
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre ya está en uso.',

            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no debe exceder los 255 caracteres.',
            'code.unique' => 'El código ya está en uso.',

            'description.max' => 'La descripción no debe exceder los 255 caracteres.',
        ]
        );

        try {

            // ------------------ CREAR COMPANY ------------------
            Company::create($validated);

            return redirect()
                ->back()
                ->with('success', 'Empresa creada correctamente');

        } catch (\Throwable $e) {

            Log::error('Error al crear empresa', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la empresa.');
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
    public function update(Request $request, Company $company)
    {
        //->ignore($position->id)
        $validated = $request->validate([
            // ------------------ COMPANY ------------------
            'name' => ['required', 'string', 'max:255', 
                Rule::unique('companies', 'name')->ignore($company->id)],
            'code' => ['required', 'string', 'max:255', 
                Rule::unique('companies', 'code')->ignore($company->id)],
            'description' => ['max:255'],
        ],
        [
            // ---------------- COMPANY ----------------
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre ya está en uso.',

            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no debe exceder los 255 caracteres.',
            'code.unique' => 'El código ya está en uso.',

            'description.max' => 'La descripción no debe exceder los 255 caracteres.',
        ]
        );

        try {

            // ------------------ CREAR COMPANY ------------------
            $company->update($validated);

            return redirect()
                ->back()
                ->with('success', 'Empresa creada correctamente');

        } catch (\Throwable $e) {

            Log::error('Error al crear empresa', [
                'company_id' => $company->id,
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la empresa.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        try{

            $company->delete();
            
            return redirect()
                ->back()
                ->with('success', 'Registro eliminado exitosamente.');
        } catch (\Throwable $e) {

            Log::error('Error al crear empresa', [
                'payload' => $company,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la empresa.');
        }
    }
}
