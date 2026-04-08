<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\WeeklyAssistence;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Incidence;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use App\Services\WeeklyAssistenceService;
use Illuminate\Support\Facades\Auth;

class WeeklyAssistencesController
{
    /**
     * Display a listing of the resource.
     */

    private function getDateFromWeekDay($week, $year, $dayIndex){
        $dto = new \DateTime();
        $dto->setISODate($year, $week, $dayIndex);
        return $dto->format('Y-m-d');
    }

    public function index( Request $request ){
        return Inertia::render('WeeklyAssistances/Index', []);
    }

    public function filter_data( Request $request ){
        $data = $this->getFilteredData($request);
        return response()->json([
            'data'  => $data,
        ]);

    }

    public function downloadAll(Request $request)
    {
        $data = $this->getFilteredData($request);
        $campos = $request->campos ?? [];

        $dataFiltrado = collect($data)->map(function ($row) use ($campos) {
            $registro = [
                'Clave'             => $row->employee_id,
                'Empleado'          => $row->employee_name,
                'Departamento'      => $row->department_name,
                'Puesto'            => $row->position_name,
                'Fecha Ingreso'     => $row->entry_date,

                'Lunes'             => $row->monday_code,
                'Horario Lunes'     => $this->transformChecadas($row->monday_data),

                'Martes'            => $row->tuesday_code,
                'Horario Martes'    => $this->transformChecadas($row->tuesday_data),

                'Miercoles'         => $row->wednesday_code,
                'Horario Miercoles' => $this->transformChecadas($row->wednesday_data),

                'Jueves'            => $row->thursday_code,
                'Horario Jueves'    => $this->transformChecadas($row->thursday_data),

                'Viernes'           => $row->friday_code,
                'Horario Viernes'   => $this->transformChecadas($row->friday_data),

                'Sabado'            => $row->saturday_code,
                'Horario Sabado'    => $this->transformChecadas($row->saturday_data),

                'Domingo'           => $row->sunday_code,
                'Horario Domingo'   => $this->transformChecadas($row->sunday_data),

                'Prima Dominical'   => $row->sunday_premium,
                'Dobles'            => $row->total_horas_dobles,
                'Triples'           => $row->total_horas_triples,
                'Faltas'            => $row->faltas,

                'Planta'            => $row->planta,
                'Semana'            => $row->week_number,
                'Año'               => $row->week_year,
            ];

            if (empty($campos)) {
                return $registro;
            }
            return collect($registro)->only($campos)->toArray();
        });

        $filename = 'Reporte_Asistencias_' . now()->format('Ymd_His');

        return Excel::download(
            new EmployeesExport($dataFiltrado->values()->toArray()),
            $filename . '.xlsx'
        );
    }

    private function transformChecadas($json)
    {
        if (!$json) return $json;

        $data = json_decode($json, true);

        if (!isset($data['Checadas']) || !is_array($data['Checadas'])) {
            return json_encode($data);
        }

        $data['Checadas'] = collect($data['Checadas'])
            ->pluck('access_time')
            ->values()
            ->toArray();

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function getFilteredData($request)
    {

        $data = WeeklyAssistence::index($request);

        $data = collect($data)->map(function ($item) {

            $dias = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            $absCodes = ['F', 'FT'];

            $item->horarios = [];
            $total_dobles = 0;
            $total_triples = 0;
            $faltas = 0;
            $sunday_premium = 0;

            foreach ($dias as $dia) {

                $code = strtoupper(trim((string) $item->{$dia . '_code'}));
                if (in_array($code, $absCodes)) {
                    $faltas++;
                }

                $json = $item->{$dia . '_data'} ?? null;
                $horario = json_decode($json, true);

                if (is_array($horario)) {

                    $dobles  = floatval($horario['Horas dobles'] ?? 0);
                    $triples = floatval($horario['Horas triples'] ?? 0);

                    $total_dobles  += $dobles;
                    $total_triples += $triples;

                    $sunday_premium += $horario['sunday_premium'] ?? 0;
                }
            }

            if ($sunday_premium >= 1) {
                $sunday_premium = 1;
            }

            $item->faltas = $faltas;
            $item->total_horas_dobles = $total_dobles;
            $item->total_horas_triples = $total_triples;
            $item->sunday_premium = $sunday_premium;

            return $item;

        });

        return $data;
    }

    public function weeklyAssistenceCheckTurn( Request $request, WeeklyAssistenceService $WeeklyAssistenceService ){

        $resultado = $WeeklyAssistenceService->revisarAsistencia( (int) $request->id, $request->validity_from);

        return response()->json($resultado);
    }


}
