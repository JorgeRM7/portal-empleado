<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatusReason;
use Inertia\Inertia;

class StatusReasonsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statusReasons = StatusReason::all();

        return Inertia::render('StatusReasons/Index', [
            'StatusReasons' => $statusReasons
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

        StatusReason::create($request->all());

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
    public function update(Request $request, StatusReason $statusReason)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $statusReason->update($request->all());

        return redirect()->back()->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StatusReason $statusReason)
    {
        $statusReason->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
