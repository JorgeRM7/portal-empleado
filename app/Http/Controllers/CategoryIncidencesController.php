<?php

namespace App\Http\Controllers;

use App\Models\CategoryIncidence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CategoryIncidencesController
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categoryIncidences = CategoryIncidence::all();

        return Inertia::render('CategoryIncidences/Index', [
            'CategoryIncidences' => $categoryIncidences
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
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category_incidences', 'code')
            ]
        ],
        [
            'code.unique'  => 'El codigo ya está registrado.',
        ]);

        CategoryIncidence::create($request->all());

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
    public function update(Request $request, CategoryIncidence $categoryIncidence)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category_incidences', 'code')->ignore($categoryIncidence->id),
            ]
        ],
        [
            'code.unique'  => 'El codigo ya está registrado.',
        ]);

        $categoryIncidence->update($request->all());

        return redirect()->back()->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryIncidence $categoryIncidence)
    {
        $categoryIncidence->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
