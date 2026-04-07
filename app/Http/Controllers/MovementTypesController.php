<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovementType;
use Inertia\Inertia;

class MovementTypesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $movementTypes = MovementType::all();

        return Inertia::render('MovementTypes/Index', [
            'MovementTypes' => $movementTypes
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
            'name' => 'required|string|max:255',
        ]);

        MovementType::create($request->all());

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
    public function update(Request $request, MovementType $movementType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $movementType->update($request->all());

        return redirect()->back()->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovementType $movementType)
    {
        $movementType->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
