<?php

namespace App\Http\Controllers;

use App\Models\PayrollInvoiceType;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PayrollInvoiceTypesController
{
    public function index( Request $request ){

        $data = PayrollInvoiceType::get();
        return Inertia::render('PayrollInvoicesTypes/Index', [
            "data" => $data,
        ]);
    }
    public function create()
    {
        return Inertia::render('PayrollInvoicesTypes/Create', []);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|int',
        ]);

        PayrollInvoiceType::create($validated);

        return redirect()->back()->with('success', 'Evento creado correctamente');
    }

    public function show( PayrollInvoiceType $PayrollInvoiceType)
    {
        return Inertia::render('PayrollInvoicesTypes/Show', [
            'data' => $PayrollInvoiceType
        ]);
    }

    public function edit( PayrollInvoiceType $PayrollInvoiceType )
    {
        return Inertia::render('PayrollInvoicesTypes/Edit', [
            'data' => $PayrollInvoiceType
        ]);
    }

    public function update(Request $request, PayrollInvoiceType $PayrollInvoiceType){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|int',
        ]);

        $PayrollInvoiceType->update($validated);

        return redirect()->back()->with('success', 'Evento actualizado correctamente');
    }

    public function destroy( $recordID )
    {
        $record= PayrollInvoiceType::find($recordID);

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
