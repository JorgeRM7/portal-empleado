<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Departments;
use App\Models\PayrollTypes;
use Illuminate\Validation\Rule;

class DepartmentController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Departments::index();

        return Inertia::render('Departments/Index', [
            'Departments' => $departments
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $payrollTypes = PayrollTypes::select('id', 'name')->get();

        return Inertia::render('Departments/Create', [
            'PayrollTypes' => $payrollTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Departments $department)
    {

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name'),
            ],
            'external_id' => ['nullable', 'integer', 'min:0'],
            'payroll_type_id' => ['nullable', 'integer'],
            'holiday' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre ya está en uso.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
        ]);

        try {

            Departments::create([
                'name' => $validated['name'],
                'external_id' => $validated['external_id'] ?? null,
                'payroll_type_id' => $validated['payroll_type_id'] ?? null,
                'holiday' => $validated['holiday'] ?? 0,
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('departments.index')
                ->with('success', 'Departamento creado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear el departamento.');
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payrollTypes = PayrollTypes::select('id', 'name')->get();

        return Inertia::render('Departments/Show',[
            'Departments' => Departments::findOrFail($id),
            'PayrollTypes' => $payrollTypes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $payrollTypes = PayrollTypes::select('id', 'name')->get();

        return Inertia::render('Departments/Edit',[
            'Departments' => Departments::findOrFail($id),
            'PayrollTypes' => $payrollTypes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Departments $department)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'external_id' => ['nullable', 'integer', 'min:0'],
            'payroll_type_id' => ['nullable', 'integer'],
            'holiday' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre ya está en uso.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
        ]);

        try {

            $department->update([
                'name' => $validated['name'],
                'external_id' => $validated['external_id'] ?? null,
                'payroll_type_id' => $validated['payroll_type_id'] ?? null,
                'holiday' => $validated['holiday'] ?? 0,
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('departments.index')
                ->with('success', 'Departamento actualizado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar el departamento.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Departments $department)
    {
        
        try {

            $department->delete();

            return redirect()
                ->back()
                ->with('success', 'Departamento eliminado correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al eliminar el departamento.');
        }
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:departments,id',
        ]);

        Departments::whereIn('id', $request->ids)->delete();

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
            $h = preg_replace('/\s*\(.*\)$/u', '', $h); // elimina (0 = No, 1 = Sí)
            $h = mb_strtolower($h, 'UTF-8');
            $h = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $h);
            return $h;
        }, $rawHeaders);

        $headerMap = [
            'clave' => 'id',
            'clave netsuite' => 'external_id',
            'nombre' => 'name',
            'descripcion' => 'description',
            'regla de dia festivo' => 'holiday',
            'id tipo de asiento' => 'payroll_type_id',
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
                'requeridas' => $requiredDbColumns,
                'existentes' => $headerMap,
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

                // Default para holiday
                if (!array_key_exists('holiday', $data) || $data['holiday'] === null) {
                    $data['holiday'] = 0;
                }

                // Validación por fila
                $validator = Validator::make(
                    $data,
                    [
                        'name' => ['required', 'string', 'max:255'],

                        'external_id' => ['nullable', 'string', 'max:255'],
                        'description' => ['nullable', 'string', 'max:500'],

                        'holiday' => ['nullable', 'in:0,1'],
                        'payroll_type_id' => ['nullable', 'integer'],
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
                        'external_id' => 'Clave NetSuite',
                        'name' => 'Nombre',
                        'description' => 'Descripcion',
                        'holiday' => 'Regla de dia festivo',
                        'payroll_type_id' => 'Id tipo de asiento',
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
                $nameQuery = Departments::withTrashed()
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

                // ------------- CREATE / UPDATE  -------------
                if (!empty($data['id'])) {

                    $departments = Departments::withTrashed()->find($data['id']);

                    if ($departments) {
                        // Si estaba eliminado, restaurar
                        if ($departments->trashed()) {
                            $departments->restore();
                            $debug[] = "Fila " . ($index + 2) . ": ID {$data['id']} restaurado";
                        }

                        unset($data['id']);
                        $departments->update($data);
                        $updated++;

                    } else {
                        Departments::create($data);
                        $created++;
                        $debug[] = "Fila " . ($index + 2) . ": ID {$data['id']} creado";
                    }

                } else {
                    // CREATE sin ID
                    Departments::create($data);
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
        $headerMap = [
            'Clave' => 'id',
            'Clave NetSuite' => 'external_id',
            'Nombre' => 'name',
            'Descripcion' => 'description',
            'Regla de dia festivo (1 = Sí, 0 = No)' => 'holiday',
            'Id tipo de asiento' => 'payroll_type_id',
        ];

        return response()->streamDownload(
            function () use ($headerMap) {

                // BOM UTF-8 para Excel (acentos, ñ, etc.)
                echo "\xEF\xBB\xBF";

                // Abrir salida estándar
                $handle = fopen('php://output', 'w');

                // Escribir encabezados (solo las claves visibles)
                fputcsv($handle, array_keys($headerMap));

                fclose($handle);
            },
            'plantilla_importacion_departmentos.csv',
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="plantilla_importacion_departmentos.csv"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]
        );
    }

}
