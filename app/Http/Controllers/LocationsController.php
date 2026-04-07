<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\City;
use App\Models\State;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class LocationsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::index();

        return Inertia::render('Locations/Index', [
            'Locations' => $locations
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $states = State::select("id", "name")->get();
        $cities = City::select("id", "name", "state_id")->get();

        return Inertia::render('Locations/Create', [
            'States' => $states,
            'Cities' => $cities
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $validated = $request->validate(
            [
                'name' => ['required'],
                'city_id' => ['required'],
                'state_id' => ['nullable'],
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
                'name' => 'Colonia',
                'city_id' => 'Ciudad',
                'state_id' => 'Estado',
            ]
        );

        try {

            Location::create($validated);

            return redirect()
                ->route('locations.index')
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
    public function show(Location $location)
    {

        $states = State::select("id", "name")->get();
        $cities = City::select("id", "name", "state_id")->get();

        return Inertia::render('Locations/Show', [
            'Location' => $location,
            'States' => $states,
            'Cities' => $cities
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {

        $states = State::select("id", "name")->get();
        $cities = City::select("id", "name", "state_id")->get();

        return Inertia::render('Locations/Edit', [
            'Location' => $location,
            'States' => $states,
            'Cities' => $cities
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate(
            [
                'name' => ['required'],
                'city_id' => ['required'],
                'state_id' => ['nullable'],
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
                'name' => 'Colonia',
                'city_id' => 'Ciudad',
                'state_id' => 'Estado',
            ]
        );

        try {

            $location->name = $validated['name'];
            $location->city_id = $validated['city_id'];
            $location->state_id = $validated['state_id'];

            $location->save();

            return redirect()
                ->route('locations.index')
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
    public function destroy(Location $location)
    {
        // $location->delete();
        // return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

}
