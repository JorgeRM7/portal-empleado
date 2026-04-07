<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Departments;
use App\Models\EmployeeEfficiency;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Logs;


class EmployeeEfficiencyController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->select('branch_offices.id', 'branch_offices.code')
            ->get();

        $branchOfficeIds = $branchOffices->pluck('id');

        $employees = Employee::select('id', 'full_name')
            ->whereIn('status', ['entry', 'reentry'])
            ->whereIn('branch_office_id', $branchOfficeIds)
            ->orderBy('id')
            ->get();

        $departments = Departments::select('id', 'name')->get();

        $positions = Position::select('id', 'name')->get();

        $employee = Employee::where('user_id', $user->id)->first();
        $employeeId = $employee?->id;

        return Inertia::render('EmployeeEfficiency/Index', [
            'branchOffices' => $branchOffices,
            'departments' => $departments,
            'employees' => $employees,
            'position' => $positions,
            'authEmployeeId' => $employeeId,
        ]);
    }

    public function filter_data_efficiency(Request $request)
    {
        $branchOfficeIds = $request->input('branch_office_id', []);
        $departmentId    = $request->input('department_id', []);
        $positionId      = $request->input('position_id');
        $estatus         = $request->input('estatus');
        $month           = $request->input('month');
        $employeeIds     = $request->input('employee_ids', []);
        // $includeDeleted  = $request->boolean('include_deleted');

        $user = auth()->user();

        $data = EmployeeEfficiency::index_filter([
            'branch_office_id' => $branchOfficeIds,
            'department_id'    => $departmentId,
            'position_id'      => $positionId,
            'estatus'          => $estatus,
            'employee_ids'     => $employeeIds,
            // 'include_deleted'  => $includeDeleted,
            'month'            => $month,
        ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function sodexo_validos(Request $request)
    {
        $month = $request->input('month');

        if (!$month) {
            return response()->json([]);
        }

        $user = auth()->user();
        $id_user = $user->id;

        $empleadoLogueado = DB::table('employees')
            ->where('user_id', $id_user)
            ->whereNull('deleted_at')
            ->first();

        if (!$empleadoLogueado) {
            return response()->json(['error' => 'Empleado no encontrado']);
        }

        // Obtenemos la lista de subordinados/empleados con prestación Sodexo
        $empleados = EmployeeEfficiency::empleados_sodexo($empleadoLogueado->id, $user->name);

        // Usamos la fecha actual del sistema
        $fechaActual = Carbon::now()->startOfDay();

        $empleadosValidos = [];

        foreach ($empleados as $empleado) {

            // Prioridad de fecha (Reingreso > Ingreso)
            $fechaReferencia = $empleado->reentry_date ?: $empleado->entry_date;

            if (!$fechaReferencia) {
                continue;
            }

            $fechaIngreso = Carbon::parse($fechaReferencia)->startOfDay();
            $diaIngreso = $fechaIngreso->day;

            $fechaCumple3Meses = $fechaIngreso->copy()->addMonths(3);

            if ($diaIngreso <= 7) {
                $fechaCumple3Meses->day(7);
            } else {
                // Si ingresó después del 7, adelanta un mes adicional
                $fechaCumple3Meses->addMonth()->day(7);
            }
            $fechaCumple3Meses->startOfDay();

            // Límite Superior: Primeros 6 días del mes de cumplimiento
            $limiteSuperiorAviso = $fechaCumple3Meses->copy()->day(6);

            // Límite Inferior: 10 días antes de la fecha de cumplimiento
            $limiteInferiorAviso = $fechaCumple3Meses->copy()->subDays(10);
            $esValido = false;

            // Condición 1: Ya cumplió los 3 meses (o es el día exacto)
            if ($fechaActual->greaterThanOrEqualTo($fechaCumple3Meses)) {
                $esValido = true;
            }
            // Condición 2: Está dentro del rango de aviso (10 días antes)
            else if ($fechaActual->greaterThanOrEqualTo($limiteInferiorAviso) &&
                    $fechaActual->lessThanOrEqualTo($limiteSuperiorAviso)) {
                $esValido = true;
            }

            if ($esValido) {
                $empleadosValidos[] = $empleado;
            }
        }

        return response()->json($empleadosValidos);
    }

    public function import_sodexo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');

        // 🔥 Detectar delimitador automáticamente
        $fileContent = file_get_contents($file->getRealPath());
        if (strpos($fileContent, "\t") !== false) {
            $delimiter = "\t";
        } elseif (strpos($fileContent, ';') !== false) {
            $delimiter = ';';
        } else {
            $delimiter = ',';
        }

        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo leer el archivo'
            ], 400);
        }

        $lineNumber = 0;
        $successCount = 0;
        $errorCount = 0;

        // 🔥 NUEVOS ARRAYS DETALLADOS
        $successes = [];
        $errors = [];

        $user = auth()->user();

        DB::beginTransaction();

        try {

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {

                $lineNumber++;

                if (!isset($row[0]) || count(array_filter($row)) === 0) {
                    continue;
                }

                // 🔥 Si todo viene en una sola columna (caso raro), separarlo
                if (count($row) === 1) {
                    $row = preg_split("/[\t,;]+/", $row[0]);
                }

                // 🔥 Limpiar valores
                $row = array_map(function ($value) {
                    return trim(str_replace('"', '', $value));
                }, $row);

                // 🔥 Saltar header
                if ($lineNumber === 1) {
                    continue;
                }

                // 🔥 Validar columnas
                if (count($row) < 4) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'employee_id' => null,
                        'employee_name' => null,
                        'error' => 'Formato incorrecto (faltan columnas)'
                    ];
                    $errorCount++;
                    continue;
                }

                $employee_id = trim($row[0]);
                $efficiency  = $row[1];
                $month       = (int) $row[2];
                $year        = (int) $row[3];

                // 🔥 Validar ID
                if (!is_numeric($employee_id)) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'employee_id' => $employee_id,
                        'employee_name' => null,
                        'error' => 'ID de empleado inválido'
                    ];
                    $errorCount++;
                    continue;
                }

                $employee = Employee::find((int)$employee_id);

                if (!$employee) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'employee_id' => $employee_id,
                        'employee_name' => null,
                        'error' => 'Empleado no existe'
                    ];
                    $errorCount++;
                    continue;
                }

                // 🔥 Validaciones
                if (!is_numeric($efficiency) || $month < 1 || $month > 12 || $year < 2000) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'employee_id' => $employee_id,
                        'employee_name' => $employee->full_name,
                        'error' => 'Datos inválidos (Porcentaje, Mes o Año)'
                    ];
                    $errorCount++;
                    continue;
                }

                $exists = EmployeeEfficiency::where('employee_id', $employee_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {

                    $nombreMes = Carbon::createFromDate($year, $month, 1)
                        ->locale('es')
                        ->monthName;

                    $errors[] = [
                        'line' => $lineNumber,
                        'employee_id' => $employee_id,
                        'employee_name' => $employee->full_name,
                        'error' => "Ya tiene registro en " . ucfirst($nombreMes) . " $year"
                    ];
                    $errorCount++;
                    continue;
                }

                // 🔥 Crear registro
                $amount    = EmployeeEfficiency::calculateAmount($efficiency);
                $parent_id = EmployeeEfficiency::resolveParentId($employee->employee_parent_id);
                $entryDate = EmployeeEfficiency::entryDate($employee);

                $efficiencyRecord = EmployeeEfficiency::create([
                    'employee_id'        => $employee->id,
                    'month'              => $month,
                    'year'               => $year,
                    'department_id'      => $employee->department_id,
                    'position_id'        => $employee->position_id,
                    'branch_office_id'   => $employee->branch_office_id,
                    'efficiency'         => $efficiency,
                    'amount'             => $amount,
                    'employee_parent_id' => $parent_id,
                    'entry_date'         => $entryDate,
                    'updated_by'         => $user->id,
                ]);

                Logs::create([
                    'action'          => 'CREATE',
                    'user_id'         => $user->id,
                    'table_name'      => 'employee_efficiencies',
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode([
                        'info' => 'Importación masiva Sodexo',
                        'after' => $efficiencyRecord->toArray()
                    ])
                ]);

                // 🔥 GUARDAR ÉXITO
                $successes[] = [
                    'line' => $lineNumber,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'efficiency' => $efficiency,
                    'month' => $month,
                    'year' => $year
                ];

                $successCount++;
            }

            fclose($handle);

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "No se importó nada",
                    'summary' => [
                        'success_count' => 0,
                        'error_count' => $errorCount
                    ],
                    'successes' => [],
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Importación finalizada",
                'summary' => [
                    'success_count' => $successCount,
                    'error_count' => $errorCount
                ],
                'successes' => $successes,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($handle)) fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Error crítico al procesar el archivo',
                'error'   => $e->getMessage()
            ], 500);
        }
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
            'month' => 'required|date',
            'efficiencies' => 'required|array|min:1',
            'efficiencies.*.employee_id' => 'required|exists:employees,id',
            'efficiencies.*.percentage' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {

            $user = auth()->user();
            $fecha = Carbon::parse($request->month);

            $month = $fecha->month;
            $year  = $fecha->year;

            foreach ($request->efficiencies as $eff) {
                $employee = Employee::find($eff['employee_id']);
                if (!$employee) continue;

                $before = EmployeeEfficiency::where([
                    'employee_id' => $employee->id,
                    'month'       => $month,
                    'year'        => $year,
                ])->first();

                $percentage = $eff['percentage'];
                $amount     = EmployeeEfficiency::calculateAmount($percentage);
                $first      = EmployeeEfficiency::resolveParentId($employee->employee_parent_id);
                $entryDate  = EmployeeEfficiency::entryDate($employee);

                $efficiencyRecord = EmployeeEfficiency::updateOrCreate(
                    ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
                    [
                        'department_id'      => $employee->department_id,
                        'position_id'        => $employee->position_id,
                        'branch_office_id'   => $employee->branch_office_id,
                        'efficiency'         => $percentage,
                        'amount'             => $amount,
                        'employee_parent_id' => $first,
                        'entry_date'         => $entryDate,
                        'updated_by'         => $user->id,
                    ]
                );

                Logs::create([
                    'action'          => $before ? 'UPDATE' : 'CREATE',
                    'user_id'         => $user->id,
                    'table_name'      => 'employee_efficiencies',
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode([
                        'before' => $before ? $before->toArray() : null,
                        'after'  => $efficiencyRecord->toArray()
                    ])
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Eficiencias guardadas correctamente'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar eficiencias',
                'error'   => $e->getMessage()
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
        $request->validate([
            'efficiency' => 'required|numeric|min:0|max:100',
            'month' => 'required',
        ]);

        try {
            $efficiencyRecord = EmployeeEfficiency::findOrFail($id);
            $before = $efficiencyRecord->toArray();

            $fecha = Carbon::parse($request->month);
            $efficiency = $request->efficiency;
            $amount = EmployeeEfficiency::calculateAmount($efficiency);

            $efficiencyRecord->update([
                'efficiency' => $efficiency,
                'amount'     => $amount,
                'month'      => $fecha->month,
                'year'       => $fecha->year,
                'updated_by' => auth()->id(),
            ]);

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_efficiencies',
                'date'            => now(),
                'relationship_id' => $efficiencyRecord->employee_id,
                'old_data'        => json_encode([
                    'before' => $before,
                    'after'  => $efficiencyRecord->getChanges()
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente'
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
        try {
            $efficiency = EmployeeEfficiency::findOrFail($id);

            $before = $efficiency->toArray();

            $efficiency->delete();

            Logs::create([
                'action'          => 'DELETE',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_efficiencies',
                'date'            => now(),
                'relationship_id' => $before['employee_id'],
                'old_data'        => json_encode([
                    'info'   => 'Eliminación de registro de eficiencia/Sodexo',
                    'before' => $before,
                    'after'  => null
                ])
            ]);

            return response()->json([
                'message' => 'Eficiencia eliminada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
