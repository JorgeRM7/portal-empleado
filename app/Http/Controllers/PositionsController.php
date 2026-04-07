<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Position;
use Illuminate\Validation\Rule;

class PositionsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $positions = Position::all();

        return Inertia::render('Positions/Index', [
            'Positions' => $positions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Positions/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Position $position)
    {

        // name                    - puesto
        // daily_salary            - salarioDiario
        // compensation_in_trial   - compensacionPrueba
        // daily_salary_in_trial   - salarioDiarioPrueba
        // type                    - tipoCompensacionPrueba
        // description             - descripcion
        // pa_in_trial             - paPrueba
        // pp_in_trial             - ppPrueba
        // pa_adjust               - pa
        // pp_adjust               - pp
        // perceptions_in_trial    - totalPercepcionesPrueba
        // perceptions_adjust      - totalPercepciones
        // net_in_trial            - netoSemanalPrueba
        // net_in_adjust           - netoSemanal
        // compensations_adjust    - compensacion
        // type_adjust             - tipoCompensacion

        $validatedData = $request->validate([
            'puesto' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
            ],
            'salarioDiario' => 'required|numeric',
            'compensacionPrueba' => 'required|numeric',
            'salarioDiarioPrueba' => 'required|numeric',
            'tipoCompensacionPrueba' => 'required|in:auto,efficiency,trial,none',
            'descripcion' => 'required|string',
            'paPrueba' => 'required|numeric|min:0|max:1',
            'ppPrueba' => 'required|numeric|min:0|max:1',
            'pa' => 'required|numeric|min:0|max:1',
            'pp' => 'required|numeric|min:0|max:1',
            'totalPercepcionesPrueba' => 'required|numeric',
            'totalPercepciones' => 'required|numeric',
            'netoSemanalPrueba' => 'required|numeric',
            'netoSemanal' => 'required|numeric',
            'compensacion' => 'required|numeric',
            'tipoCompensacion' => 'required|in:auto,efficiency,trial,none',
        ],
        [
            'puesto.unique'  => 'El nombre del puesto ya está registrado.',
            'paPrueba.max' => 'El %PA de prueba no puede ser mayor a 1',
            'ppPrueba.max' => 'El %PP de prueba no puede ser mayor a 1',
            'pa.max' => 'El %PA ajustado no puede ser mayor a 1',
            'pp.max' => 'El %PP ajustado no puede ser mayor a 1',
        ]);

        // Crear el registro en la tabla positions
        $position = Position::create([
            'name' => $validatedData['puesto'],
            'daily_salary' => $validatedData['salarioDiario'],
            'compensation_in_trial' => $validatedData['compensacionPrueba'],
            'daily_salary_in_trial' => $validatedData['salarioDiarioPrueba'],
            'type' => $validatedData['tipoCompensacionPrueba'],
            'description' => $validatedData['descripcion'],
            'pa_in_trial' => $validatedData['paPrueba'],
            'pp_in_trial' => $validatedData['ppPrueba'],
            'pa_adjust' => $validatedData['pa'],
            'pp_adjust' => $validatedData['pp'],
            'perceptions_in_trial' => $validatedData['totalPercepcionesPrueba'],
            'perceptions_adjust' => $validatedData['totalPercepciones'],
            'net_in_trial' => $validatedData['netoSemanalPrueba'],
            'net_in_adjust' => $validatedData['netoSemanal'],
            'compensations_adjust' => $validatedData['compensacion'],
            'type_adjust' => $validatedData['tipoCompensacion'],
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Puesto creado correctamente');


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Inertia::render('Positions/Show',[
            'Position' => Position::findOrFail($id)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('Positions/Edit',[
            'Position' => Position::findOrFail($id)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position)
    {
        $validatedData = $request->validate([
            'puesto' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->ignore($position->id)
            ],
            'salarioDiario' => 'required|numeric|min:0',
            'compensacionPrueba' => 'required|numeric|min:0',
            'salarioDiarioPrueba' => 'required|numeric|min:0',
            'tipoCompensacionPrueba' => 'required|in:auto,efficiency,trial,none',
            'descripcion' => 'required|string|max:500',
            'paPrueba' => 'required|numeric|min:0|max:1',
            'ppPrueba' => 'required|numeric|min:0|max:1',
            'pa' => 'required|numeric|min:0|max:1',
            'pp' => 'required|numeric|min:0|max:1',
            'totalPercepcionesPrueba' => 'required|numeric|min:0',
            'totalPercepciones' => 'required|numeric|min:0',
            'netoSemanalPrueba' => 'required|numeric|min:0',
            'netoSemanal' => 'required|numeric|min:0',
            'compensacion' => 'required|numeric|min:0',
            'tipoCompensacion' => 'required|in:auto,efficiency,trial,none',
        ], [
            'puesto.unique'  => 'El nombre del puesto ya está registrado.',
            'paPrueba.max' => 'El %PA de prueba no puede ser mayor a 1',
            'ppPrueba.max' => 'El %PP de prueba no puede ser mayor a 1',
            'pa.max' => 'El %PA ajustado no puede ser mayor a 1',
            'pp.max' => 'El %PP ajustado no puede ser mayor a 1',
        ]);

        // Actualizar el registro
        $position->update([
            'name' => $validatedData['puesto'],
            'daily_salary' => $validatedData['salarioDiario'],
            'compensation_in_trial' => $validatedData['compensacionPrueba'],
            'daily_salary_in_trial' => $validatedData['salarioDiarioPrueba'],
            'type' => $validatedData['tipoCompensacionPrueba'],
            'description' => $validatedData['descripcion'],
            'pa_in_trial' => $validatedData['paPrueba'],
            'pp_in_trial' => $validatedData['ppPrueba'],
            'pa_adjust' => $validatedData['pa'],
            'pp_adjust' => $validatedData['pp'],
            'perceptions_in_trial' => $validatedData['totalPercepcionesPrueba'],
            'perceptions_adjust' => $validatedData['totalPercepciones'],
            'net_in_trial' => $validatedData['netoSemanalPrueba'],
            'net_in_adjust' => $validatedData['netoSemanal'],
            'compensations_adjust' => $validatedData['compensacion'],
            'type_adjust' => $validatedData['tipoCompensacion'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Puesto actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        $position->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:positions,id',
        ]);

        Position::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Registros eliminados');
    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));

        if (count($rows) < 2) {
            return response()->json([
                'message' => 'El archivo no contiene registros válidos.'
            ], 422);
        }

        // Encabezados
        // --------------------------------------------------------------------------
        $rawHeaders = array_map('trim', array_shift($rows));
        $rawHeaders = array_map(function ($h) {
            $h = trim($h);
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // BOM campo que se agrega en Excel automáticamente
            $h = mb_strtolower($h, 'UTF-8');
            $h = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $h);
            return $h;
        }, $rawHeaders);

        $headerMap = [
            'id' => 'id',
            'puesto' => 'name',
            'descripcion' => 'description',
            'salario diario' => 'daily_salary',
            'salario prueba' => 'daily_salary_in_trial',
            'comp prueba' => 'compensation_in_trial',
            'comp ajuste' => 'compensations_adjust',
            'determinacion' => 'type',
            'prem asist prueba' => 'pa_in_trial',
            'prem punt prueba' => 'pp_in_trial',
            'prem asist ajuste' => 'pa_adjust',
            'prem punt ajuste' => 'pp_adjust',
            'percepciones prueba' => 'perceptions_in_trial',
            'percepciones ajuste' => 'perceptions_adjust',
            'neto prueba' => 'net_in_trial',
            'neto ajuste' => 'net_in_adjust',
            'tipo ajuste' => 'type_adjust',
        ];

        // Columnas obligatorias
        // --------------------------------------------------------------------------
        $requiredDbColumns = array_values(array_diff(
            $headerMap,
            ['id']
        ));

        // Resolver índices dinámicos
        // --------------------------------------------------------------------------
        $headerIndexes = [];

        foreach ($rawHeaders as $index => $header) {
            if (!$header) {
                continue; // encabezado vacío → ignorar
            }

            if (isset($headerMap[$header])) {
                $headerIndexes[$headerMap[$header]] = $index;
            }
        }

        // Validar que existan TODAS las columnas obligatorias
        // --------------------------------------------------------------------------
        $missingColumns = [];

        foreach ($requiredDbColumns as $column) {
            if (!array_key_exists($column, $headerIndexes)) {
                // buscar nombre humano solo para mensaje
                $humanName = array_search($column, $headerMap, true);
                $missingColumns[] = $humanName ?? $column;
            }
        }

        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'Faltan columnas obligatorias en el archivo.',
                'missing_columns' => $missingColumns,
            ], 422);
        }

        // Procesamiento
        // --------------------------------------------------------------------------

        $created = 0;
        $updated = 0;
        $errors  = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {

                $data = [];

                foreach ($headerIndexes as $dbColumn => $csvIndex) {
                    $data[$dbColumn] = $row[$csvIndex] ?? null;
                }

                // Ignorar filas vacías / informativas
                $hasData = false;

                foreach ($data as $value) {
                    if ($value !== null && trim($value) !== '') {
                        $hasData = true;
                        break;
                    }
                }

                if (!$hasData) {
                    continue;
                }

                $debug[] = [
                    'headers' => $headerIndexes,
                    'row' => $row,
                    'data' => $data,
                ];

                // Normalizar valores vacíos
                foreach ($data as $key => $value) {
                    $data[$key] = $value === '' ? null : trim($value);
                }

                // Validación por fila
                $validator = Validator::make(
                    $data,
                    [
                        'name' => ['required', 'string', 'max:255'],
                        'description' => ['required', 'string'],

                        'daily_salary' => ['required', 'numeric', 'min:0'],
                        'daily_salary_in_trial' => ['required', 'numeric', 'min:0'],

                        'compensation_in_trial' => ['required', 'numeric', 'min:0'],
                        'compensations_adjust' => ['required', 'numeric', 'min:0'],

                        'pa_in_trial' => ['required', 'numeric', 'min:0', 'max:1'],
                        'pp_in_trial' => ['required', 'numeric', 'min:0', 'max:1'],
                        'pa_adjust' => ['required', 'numeric', 'min:0', 'max:1'],
                        'pp_adjust' => ['required', 'numeric', 'min:0', 'max:1'],

                        'perceptions_in_trial' => ['required', 'numeric', 'min:0'],
                        'perceptions_adjust' => ['required', 'numeric', 'min:0'],

                        'net_in_trial' => ['required', 'numeric', 'min:0'],
                        'net_in_adjust' => ['required', 'numeric', 'min:0'],

                        'type' => ['required', 'in:auto,efficiency,trial,none'],
                        'type_adjust' => ['required', 'in:auto,efficiency,trial,none'],
                    ],
                    [
                        // Required
                        'required' => 'El campo :attribute es obligatorio.',

                        // Tipos
                        'string' => 'El campo :attribute debe ser texto.',
                        'numeric' => 'El campo :attribute debe ser un número.',
                        'integer' => 'El campo :attribute debe ser un número entero.',
                        'in' => 'El valor seleccionado para :attribute no es válido.',
                        'min' => 'El campo :attribute debe ser al menos :min o contener al menos :min caracteres.',
                        'max' => 'El campo :attribute no debe mayor a :max o exceder :max caracteres.',
                    ],
                    [
                        // Traducción de atributos (CLAVE)
                        'name' => 'Puesto',
                        'description' => 'Descripción',

                        'daily_salary' => 'Salario diario',
                        'daily_salary_in_trial' => 'Salario en prueba',

                        'compensation_in_trial' => 'Compensación en prueba',
                        'compensations_adjust' => 'Compensación ajuste',

                        'pa_in_trial' => 'Premio asistencia prueba',
                        'pp_in_trial' => 'Premio puntualidad prueba',
                        'pa_adjust' => 'Premio asistencia ajuste',
                        'pp_adjust' => 'Premio puntualidad ajuste',

                        'perceptions_in_trial' => 'Percepciones prueba',
                        'perceptions_adjust' => 'Percepciones ajuste',

                        'net_in_trial' => 'Neto prueba',
                        'net_in_adjust' => 'Neto ajuste',

                        'type' => 'Determinación',
                        'type_adjust' => 'Tipo ajuste',
                    ]
                );

                $rowErrors = [];

                // ---------------- VALIDACIONES BASE ----------------
                if ($validator->fails()) {
                    $rowErrors = array_merge(
                        $rowErrors,
                        $validator->errors()->all()
                    );
                }

                // ---------------- VALIDACIÓN NOMBRE DUPLICADO ----------------
                $nameQuery = Position::withTrashed()
                    ->where('name', $data['name']);

                if (!empty($data['id'])) {
                    // UPDATE → excluir el mismo ID
                    $nameQuery->where('id', '!=', $data['id']);
                }

                if ($nameQuery->exists()) {
                    $rowErrors[] = "El puesto '{$data['name']}' ya existe en otro registro.";
                }

                // ---------------- SI HUBO ERRORES, REGISTRARLOS ----------------
                if (!empty($rowErrors)) {
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => $rowErrors,
                    ];
                    continue;
                }

                // if ($validator->fails()) {
                //     $errors[] = [
                //         'row' => $index + 2,
                //         'errors' => $validator->errors()->all(),
                //     ];
                //     continue;
                // }

                // ------------- CREATE / UPDATE  -------------
                if (!empty($data['id'])) {

                    $position = Position::withTrashed()->find($data['id']);

                    if ($position) {
                        // Si estaba eliminado, restaurar
                        if ($position->trashed()) {
                            $position->restore();
                            $debug[] = "Fila " . ($index + 2) . ": ID {$data['id']} restaurado";
                        }

                        unset($data['id']);
                        $position->update($data);
                        $updated++;

                    } else {
                        Position::create($data);
                        $created++;
                        $debug[] = "Fila " . ($index + 2) . ": ID {$data['id']} creado";
                    }

                } else {
                    // CREATE sin ID
                    Position::create($data);
                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Importación completada. creados: {$created} , actualizados: {$updated}.",
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'debug' => $debug,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al procesar el archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/templates/catalogs/positions_import_template.csv');

        if (!file_exists($path)) {
            abort(404, 'La plantilla no existe.');
        }

        return response()->streamDownload(
            function () use ($path) {
                readfile($path);
            },
            'plantilla_importacion_puestos.csv',
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="plantilla_importacion_puestos_v2.csv"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]
        );
    }


}
