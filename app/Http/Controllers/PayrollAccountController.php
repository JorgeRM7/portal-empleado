<?php

namespace App\Http\Controllers;

use App\Models\PayrollAccount;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PayrollAccountController
{
    public function index( Request $request ){

        $data = PayrollAccount::get();
        return Inertia::render('PayrollAccount/Index', [
            "data" => $data,
        ]);
    }
    public function create()
    {
        return Inertia::render('PayrollAccount/Create', []);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string',
            'number' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $PayrollAccount = PayrollAccount::create($validated);

        return redirect()->back()->with('success', 'Evento creado correctamente');
    }

    public function show( PayrollAccount $PayrollAccount)
    {
        return Inertia::render('PayrollAccount/Show', [
            'data' => $PayrollAccount
        ]);
    }

    public function edit( PayrollAccount $PayrollAccount )
    {
        return Inertia::render('PayrollAccount/Edit', [
            'data' => $PayrollAccount
        ]);
    }

    public function update(Request $request, PayrollAccount $PayrollAccount){
         $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string',
            'number' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $PayrollAccount->update($validated);

        return redirect()->back()->with('success', 'Evento actualizado correctamente');
    }

    public function destroy( $recordID )
    {
        $record= PayrollAccount::find($recordID);

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
