<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reason;
use Inertia\Inertia;

class ReasonsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reasons = Reason::all();

        return Inertia::render('Reasons/Index', [
            'Reasons' => $reasons
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
            'description' => 'required|string|max:500',
            'type' => 'required|string|max:255',
        ]);

        Reason::create($request->all());

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
    public function update(Request $request, Reason $reason)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'type' => 'required|string|max:255',
        ]);

        $reason->update($request->all());

        return redirect()->back()->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reason $reason)
    {
        $reason->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
