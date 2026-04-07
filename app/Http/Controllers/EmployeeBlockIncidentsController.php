<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\EmployeeBlockIncidents;
use App\Models\Employee;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeBlockIncidentsController
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {

        $plantaId = (int) auth()->user()->planta_empleado;
        $user = auth()->user();

        if ($plantaId === 0) {
            abort(400, 'Planta no definida');
        }

        $data = EmployeeBlockIncidents::index($plantaId);

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('BlockIncidents/Index', [
            'data' => $data,
            'branchOffices' => $branchOffices
        ]);
    }

    public function filter_data_blockIncidents(Request $request)
    {
        // dd($request->all());
        $data = EmployeeBlockIncidents::index_filter([
            'semana'  => $request->input('semana'),
            'plantas' => $request->input('plantas'),
            'estatus' => $request->input('estatus'),
        ]);

        return response()->json([
            'data' => $data
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
            'anio'              => 'required|integer|min:2000|max:2099',
            'branch_office_ids' => 'required|array|min:1',
        ]);

        try {

            $plantasExistentes = EmployeeBlockIncidents::getExistingPlants(
                $request->anio,
                $request->branch_office_ids
            );

            if (!empty($plantasExistentes)) {
                $listaCodigos = array_column($plantasExistentes, 'code');

                return response()->json([
                    'success' => false,
                    'message' => 'Las siguientes plantas ya tienen registros para este año: ' . implode(', ', $listaCodigos),
                    'duplicated_plants' => $listaCodigos
                ], 422);
            }

            EmployeeBlockIncidents::saveBlock(
                $request->anio,
                $request->branch_office_ids
            );

            return response()->json([
                'success' => true,
                'message' => 'Semanas generadas y bloqueadas con éxito para el año ' . $request->anio
            ]);

        } catch (\Exception $e) {
            // Log del error para depuración interna
            Log::error("Error en BlockIncidentsController: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud masiva'
            ], 500);
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
    public function update(Request $request, string $id)
    {
        try {
            EmployeeBlockIncidents::updateStatus($id, $request->input('estatus'));

            return response()->json([
                'success' => true,
                'message' => 'Estatus actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
