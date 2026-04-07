<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EarningDeduction;
use Inertia\Inertia;

class EarningsDeductionsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $earningsDeductions = EarningDeduction::all();

        return Inertia::render('EarningsDeductions/Index', [
            'EarningsDeductions' => $earningsDeductions
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
        $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'apply_piecework' => 'required|string|max:255',

            // Opcionales
            'description' => 'nullable|string|max:255',
            'rules' => 'nullable|string|max:255',
            'amount' => 'nullable|string|max:255',
            'apply' => 'nullable|string|max:255',
            'file_id' => 'nullable|integer',
            'need_file' => 'nullable|boolean',
            'food_pass' => 'nullable|boolean',
            'medial_pass' => 'nullable|boolean',
        ]);

        $data = $request->all();

        // Valores por defecto
        // $data['need_file'] = $data['need_file'] ?? 0;
        $data['apply'] = $data['apply'] ?? 'N';
        $data['need_file'] = 0;
        $data['food_pass'] = 0;
        $data['medial_pass'] = 0;
        $data['file_id'] = null;

        // Campo compuesto
        $data['code_complete'] = "{$data['type']}{$data['code']}";

        // Guardar
        EarningDeduction::create($data);

        return redirect()->back()->with('success', 'Registro creado exitosamente.');
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
    public function update(Request $request, EarningDeduction $earningDeduction)
    {
        $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'apply_piecework' => 'required|string|max:255',

            // Opcionales
            'description' => 'nullable|string|max:255',
            'rules' => 'nullable|string|max:255',
            'amount' => 'nullable|string|max:255',
            'apply' => 'nullable',
            'file_id' => 'nullable|integer',
            'need_file' => 'nullable|boolean',
            'food_pass' => 'nullable|boolean',
            'medial_pass' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'code',
            'name',
            'type',
            'apply_piecework',
            'description',
            'rules',
            'amount',
            'apply',
        ]);

        // Valores por defecto
        $data['apply'] = $data['apply'] ?? 'N';
        $data['need_file'] = 0;
        $data['food_pass'] = 0;
        $data['medial_pass'] = 0;
        $data['file_id'] = null;

        // Campo compuesto
        $data['code_complete'] = "{$data['type']}{$data['code']}";

        $earningDeduction->update($data);

        return redirect()->back()->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EarningDeduction $earningDeduction)
    {
        $earningDeduction->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
