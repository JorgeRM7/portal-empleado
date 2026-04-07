<?php

namespace App\Http\Controllers;

use App\Models\DiferencesResume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DiferencesResumeController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = DiferencesResume::resume_difences($request->week_year, $request->planta);
        return $data;
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $dataEstimacion = DiferencesResume::get_detalles_resumen($request->id, $request->semana_anio);
        $dataNomina = DiferencesResume::get_detalles_empleados($request->semana_anio, $request->id);

        $dataEstimacionArray = [];
        $dataNominaArray = [];
        foreach ($dataEstimacion as $key => $value) {
            $salario_hora = floor(($value->salariodiario / 8) * 100) / 100;
            $horas_extra_dobles = $value->double_overtime * $salario_hora * 2;
            $horas_extra_triple = $value->triple_overtime * $salario_hora * 3;
            $importe_a_pagar = floor(($horas_extra_dobles + $horas_extra_triple) * 100) / 100;
            $dataEstimacionArray[] = [
                'overtime' => $value->overtime,
                'double_overtime' => $value->double_overtime,
                'triple_overtime' => $value->triple_overtime,
                'description' => $value->description,
                'motivo' => $value->motivo,
                'puesto' => $value->puesto,
                'importe_a_pagar' => $importe_a_pagar,
                'salariodiario' => $value->salariodiario,

            ];
        }
        foreach ($dataNomina as $key => $value) {
            $salario_hora_empleado = floor(($value->salariodiario / 8) * 100) / 100;
            $truncated_total_overtimes = floor((($salario_hora_empleado * $value->double_overtimes_extra * 2) + (($salario_hora_empleado * $value->triple_overtimes * 3))) * 100) / 100;
            
            $dataNominaArray[] = [
                'overtime' => $value->overtimes,
                'double_overtime' => $value->double_overtimes_extra,
                'triple_overtime' => $value->triple_overtimes,
                'description' => $value->description,
                'motivo' => $value->nombremotivo,
                'puesto' => $value->name,
                'salariodiario' => $value->salariodiario,
                'importe_a_pagar' => $truncated_total_overtimes,
            ];
        }
        return [
            'dataEstimacionArray' => $dataEstimacionArray,
            'dataNominaArray' => $dataNominaArray,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiferencesResume $diferencesResume)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiferencesResume $diferencesResume)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiferencesResume $diferencesResume)
    {
        //
    }

    public function generatePdf(Request $request)
    {
        //dd($request->all());
        $headers = $request->input('headers');
        $rows = $request->input('rows');
        $name_branch_office = $request->input('name_branch_office');

        // Generamos el PDF usando una vista blade. 
        // Lo ponemos en 'landscape' (horizontal) porque las tablas suelen ser anchas.
        $pdf = Pdf::loadView('pdf.resumen', [
            'headers' => $headers,
            'rows' => $rows,
            'name_branch_office' => $name_branch_office
        ])->setPaper('letter', 'landscape');

        return $pdf->download('resumen.pdf');
    }
}
