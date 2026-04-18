<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController
{
    public function index()
    {
        return Inertia::render("Dashboard", []);
    }

    // public function show($id)
    // {
    //     $data = Dashboard::getData($id);
    //     return response()->json($data);
    // }

    public function show($id)
    {
        $data = Dashboard::getData($id);
        $data['asistencia'] = collect($data['asistencia'])->map(function ($item) {
            $diasMap = [
                'monday'    => 'lunes',
                'tuesday'   => 'martes',
                'wednesday' => 'miercoles',
                'thursday'  => 'jueves',
                'friday'    => 'viernes',
                'saturday'  => 'sabado',
                'sunday'    => 'domingo'
            ];

            $absCodes = ['F', 'FT'];
            $total_dobles = 0;
            $total_triples = 0;
            $faltas = 0;
            $sunday_premium = 0;

            foreach ($diasMap as $en => $es) {
                $code = strtoupper(trim((string) ($item->$es ?? '')));

                if (in_array($code, $absCodes)) {
                    $faltas++;
                }

                $dataField = $en . '_data';
                $json = $item->$dataField ?? null;
                $horario = is_string($json) ? json_decode($json, true) : $json;

                if (is_array($horario)) {
                    $total_dobles   += floatval($horario['Horas dobles'] ?? 0);
                    $total_triples  += floatval($horario['Horas triples'] ?? 0);
                    $sunday_premium += floatval($horario['sunday_premium'] ?? 0);
                }
            }

            $item->total_faltas = $faltas;
            $item->total_horas_dobles = $total_dobles;
            $item->total_horas_triples = $total_triples;
            $item->sunday_premium_status = ($sunday_premium >= 1) ? 1 : 0;

            return $item;
        });

        return response()->json($data);
    }

    public function vacacionesDetalle($id)
    {
        $data = Dashboard::dashboardVacaciones([
            'empleados' => [$id]
        ]);

        return response()->json($data);
    }

    public function incidenciasDetalle($id)
    {
        $data = Dashboard::dashboardIncidencias([
            'empleados' => [$id]
        ]);

        return response()->json($data);
    }
}
