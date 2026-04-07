<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Incidence;
use App\Models\CategoryIncidence;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// // ===== GENERAL =====
// name                    -- Nombre
// code                    -- Codigo
// category_incidence_id   -- Categoria
// external_code           -- Tipo de incidencia
// incapacity_code         -- Clave incapacidad
// color                   -- Color Indicativo
// description             -- Descripción

// // ===== REGLAS =====
// active                  -- Activo
// read_only               -- Solo lectura
// requires_document       -- Requiere documento
// requires_code           -- Requiere folio
// requires_auth           -- Requiere autorización
// requested_by_user       -- Solicitada por usuario

// // ===== CONFIG TIEMPOS =====
// requires_advance        -- Requiere anticipación
// advance_each            -- Tiempo
// advance_type            -- Tipo
// requires_schedule       -- Requiere horario
// requires_date           -- Requiere fecha
// requires_rest_date      -- Requiere fecha descanso

// // ===== MULTIMEDIA =====
// url_video               -- Video de Incidencia

class IncidencesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return Inertia::render('CatalogIncidences/Index', [
            'Incidence' => Incidence::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $categoryIncidences = CategoryIncidence::select('id', 'name')->get();

        return Inertia::render('CatalogIncidences/Create', [
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Incidence $incidence)
    {

        $validated = $request->validate(
            [
                // ===== GENERAL =====
                'name' => ['required', 'string', 'max:255', Rule::unique('incidences', 'name')],
                'code' => ['required', 'string', 'max:255'],
                'category_incidence_id' => ['nullable'],
                'external_code' => ['required', 'string', 'max:255'],
                'incapacity_code' => ['nullable', 'string', 'max:255'],
                'color' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1500'],

                // ===== REGLAS =====
                'active' => ['boolean'],
                'read_only' => ['boolean'],
                'requires_document' => ['boolean'],
                'requires_code' => ['boolean'],
                'requires_auth' => ['boolean'],
                'requested_by_user' => ['boolean'],

                // ===== CONFIG TIEMPOS =====
                'requires_advance' => ['boolean'],
                'advance_each' => ['required_if:requires_advance,true', 'nullable', 'integer', 'min:1'],
                'advance_type' => ['required_if:requires_advance,true', 'nullable', 'string', 'max:50'],
                'requires_schedule' => ['boolean'],
                'requires_date' => ['boolean'],
                'requires_rest_date' => ['boolean'],

                // ===== MULTIMEDIA =====
                'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200'

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
                // ===== GENERAL =====
                'name' => 'Nombre',
                'code' => 'Código',
                'category_incidence_id' => 'Categorías',
                'external_code' => 'Tipo de incidencia',
                'incapacity_code' => 'Clave incapacidad',
                'color' => 'Color Indicativo',
                'description' => 'Descripción',

                // ===== REGLAS =====
                'active' => 'Activo',
                'read_only' => 'Solo lectura',
                'requires_document' => 'Requiere documento',
                'requires_code' => 'Requiere folio',
                'requires_auth' => 'Requiere autorización',
                'requested_by_user' => 'Solicitada por usuario',

                // ===== CONFIG TIEMPOS =====
                'requires_advance' => 'Requiere anticipación',
                'advance_each' => 'Tiempo',
                'advance_type' => 'Tipo',
                'requires_schedule' => 'Requiere horario',
                'requires_date' => 'Requiere fecha',
                'requires_rest_date' => 'Requiere fecha descanso',

                // ===== MULTIMEDIA =====
                'url_video' => 'Video de Incidencia',
            ]
        );

        $validated['name'] = strtoupper($validated['name']);
        $validated['advance_each'] = $validated['advance_each'] ?? 0;
        if (!empty($validated['color'])) {
            $validated['color'] = '#' . ltrim($validated['color'], '#');
        }

        // ===== subir video =====
        if ($request->hasFile('video')) {
            $validated['url_video'] = $this->uploadVideo(
                $request->file('video'),
                $validated['name']
            );
        }

        try {

            Incidence::create($validated);

            return redirect()
                ->route('catalogs.incidences.index')
                ->with('success', 'Incidencia creada correctamente.');

        } catch (\Throwable $e) {

            Log::error('Error al crear la incidencia', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la incidencia.');
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $categoryIncidences = CategoryIncidence::select('id', 'name')->get();

        return Inertia::render('CatalogIncidences/Show',[
            'Incidence' => Incidence::findOrFail($id),
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $categoryIncidences = CategoryIncidence::select('id', 'name')->get();

        return Inertia::render('CatalogIncidences/Edit',[
            'Incidence' => Incidence::findOrFail($id),
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Incidence $incidence)
    {
        // dd($request->all());
        
        $validated = $request->validate(
            [
                // ===== GENERAL =====
                'name' => ['required', 'string', 'max:255', Rule::unique('incidences', 'name')->ignore($incidence->id)],
                'code' => ['required', 'string', 'max:255'],
                'category_incidence_id' => ['nullable'],
                'external_code' => ['required', 'string', 'max:255'],
                'incapacity_code' => ['nullable', 'string', 'max:255'],
                'color' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1500'],

                // ===== REGLAS =====
                'active' => ['boolean'],
                'read_only' => ['boolean'],
                'requires_document' => ['boolean'],
                'requires_code' => ['boolean'],
                'requires_auth' => ['boolean'],
                'requested_by_user' => ['boolean'],

                // ===== CONFIG TIEMPOS =====
                'requires_advance' => ['boolean'],
                'advance_each' => ['exclude_unless:requires_advance,true', 'nullable', 'integer', 'min:1'],
                'advance_type' => ['required_if:requires_advance,true', 'nullable', 'string', 'max:50'],
                'requires_schedule' => ['boolean'],
                'requires_date' => ['boolean'],
                'requires_rest_date' => ['boolean'],

                // ===== MULTIMEDIA =====
                'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200'

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
                // ===== GENERAL =====
                'name' => 'Nombre',
                'code' => 'Código',
                'category_incidence_id' => 'Categorías',
                'external_code' => 'Tipo de incidencia',
                'incapacity_code' => 'Clave incapacidad',
                'color' => 'Color Indicativo',
                'description' => 'Descripción',

                // ===== REGLAS =====
                'active' => 'Activo',
                'read_only' => 'Solo lectura',
                'requires_document' => 'Requiere documento',
                'requires_code' => 'Requiere folio',
                'requires_auth' => 'Requiere autorización',
                'requested_by_user' => 'Solicitada por usuario',

                // ===== CONFIG TIEMPOS =====
                'requires_advance' => 'Requiere anticipación',
                'advance_each' => 'Tiempo',
                'advance_type' => 'Tipo',
                'requires_schedule' => 'Requiere horario',
                'requires_date' => 'Requiere fecha',
                'requires_rest_date' => 'Requiere fecha descanso',

                // ===== MULTIMEDIA =====
                'url_video' => 'Video de Incidencia',
            ]
        );

        $validated['name'] = strtoupper($validated['name']);
        $validated['advance_each'] = $validated['advance_each'] ?? 0;
        if (!empty($validated['color'])) {
            $validated['color'] = '#' . ltrim($validated['color'], '#');
        }

        // ===== subir video =====
        if ($request->hasFile('video')) {
            $validated['url_video'] = $this->uploadVideo(
                $request->file('video'),
                $validated['name']
            );
        }

        try {

            $incidence->update($validated);

            return redirect()
                ->route('catalogs.incidences.index')
                ->with('success', 'Incidencia actualizada correctamente.');

        } catch (\Throwable $e) {

            Log::error('Error al actualizar la incidencia', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar la incidencia.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Incidence $incidence)
    {
        
        try {

            $incidence->delete();

            return redirect()
                ->back()
                ->with('success', 'Incidencia eliminada correctamente.');

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al eliminar la incidencia.');
        }
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:departments,id',
        ]);

        Incidence::whereIn('id', $request->ids)->delete();

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
            // ===== GENERAL =====
            'id' => 'id',
            'nombre' => 'name',
            'codigo' => 'code',
            'categoria' => 'category_incidence_id',
            'tipo de incidencia' => 'external_code',
            'clave incapacidad' => 'incapacity_code',
            'color indicativo' => 'color',
            'descripcion' => 'description',

            // ===== REGLAS =====
            'activo' => 'active',
            'solo lectura' => 'read_only',
            'requiere documento' => 'requires_document',
            'requiere folio' => 'requires_code',
            'requiere autorizacion' => 'requires_auth',
            'solicitada por usuario' => 'requested_by_user',

            // ===== CONFIG TIEMPOS =====
            'requiere anticipacion' => 'requires_advance',
            'tiempo' => 'advance_each',
            'tipo' => 'advance_type',
            'requiere horario' => 'requires_schedule',
            'requiere fecha' => 'requires_date',
            'requiere fecha descanso' => 'requires_rest_date',
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

                $booleanFields = [
                    'active',
                    'read_only',
                    'requires_document',
                    'requires_code',
                    'requires_auth',
                    'requested_by_user',
                    'requires_advance',
                    'requires_schedule',
                    'requires_date',
                    'requires_rest_date',
                ];

                foreach ($booleanFields as $field) {
                    $value = strtolower((string)($data[$field] ?? ''));

                    $data[$field] = in_array($value, ['1', 'true', 'si', 'sí', 'yes'], true) ? 1 : 0;
                }

                // Validación por fila
                $validator = Validator::make(
                    $data,
                    [
                        // ===== GENERAL =====
                        'id' => ['nullable', 'integer'],
                        'name' => ['required', 'string', 'max:255'],
                        'code' => ['required', 'string', 'max:255'],
                        'category_incidence_id' => ['nullable'],
                        'external_code' => ['required', 'string', 'max:255'],
                        'incapacity_code' => ['nullable', 'string', 'max:255'],
                        'color' => ['required', 'regex:/^#?[A-Fa-f0-9]{6}$/'],
                        'description' => ['nullable', 'string', 'max:1500'],

                        // ===== REGLAS =====
                        'active' => ['boolean'],
                        'read_only' => ['boolean'],
                        'requires_document' => ['boolean'],
                        'requires_code' => ['boolean'],
                        'requires_auth' => ['boolean'],
                        'requested_by_user' => ['boolean'],

                        // ===== CONFIG TIEMPOS =====
                        'requires_advance' => ['boolean'],
                        'advance_each' => ['required_if:requires_advance,true', 'nullable', 'integer', 'min:1'],
                        'advance_type' => ['required_if:requires_advance,true', 'nullable', 'string', 'max:50'],
                        'requires_schedule' => ['boolean'],
                        'requires_date' => ['boolean'],
                        'requires_rest_date' => ['boolean'],

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
                        // ===== GENERAL =====
                        'name' => 'Nombre',
                        'code' => 'Código',
                        'category_incidence_id' => 'Categorías',
                        'external_code' => 'Tipo de incidencia',
                        'incapacity_code' => 'Clave incapacidad',
                        'color' => 'Color Indicativo',
                        'description' => 'Descripción',

                        // ===== REGLAS =====
                        'active' => 'Activo',
                        'read_only' => 'Solo lectura',
                        'requires_document' => 'Requiere documento',
                        'requires_code' => 'Requiere folio',
                        'requires_auth' => 'Requiere autorización',
                        'requested_by_user' => 'Solicitada por usuario',

                        // ===== CONFIG TIEMPOS =====
                        'requires_advance' => 'Requiere anticipación',
                        'advance_each' => 'Tiempo',
                        'advance_type' => 'Tipo',
                        'requires_schedule' => 'Requiere horario',
                        'requires_date' => 'Requiere fecha',
                        'requires_rest_date' => 'Requiere fecha descanso',
                    ]
                );

                $rowErrors = [];

                // ---------------- VALIDACIONES BASE ----------------
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                $validated = $validator->validated();

                // ---------------- Normalizaciones finales ----------------
                $validated['name'] = strtoupper($validated['name']);
                $validated['advance_each'] = $validated['advance_each'] ?? 0;

                if (!empty($validated['color'])) {
                    $validated['color'] = '#' . ltrim($validated['color'], '#');
                }

                // ---------------- VALIDACIÓN NOMBRE DUPLICADO ----------------
                $nameQuery = Incidence::withTrashed()
                    ->where('name', $validated['name']);

                if (!empty($validated['id'])) {
                    $nameQuery->where('id', '!=', $validated['id']);
                }

                $existing = $nameQuery->first();

                if ($existing && !$existing->trashed()) {
                    $rowErrors[] = "La incidencia '{$validated['name']}' ya existe en otro registro.";
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
                if (!empty($validated['id'])) {

                    $incidence = Incidence::withTrashed()->find($validated['id']);

                    if ($incidence) {
                        // Si estaba eliminado, restaurar
                        if ($incidence->trashed()) {
                            $incidence->restore();
                            $debug[] = "Fila " . ($index + 2) . ": ID {$validated['id']} restaurado";
                        }

                        unset($validated['id']);
                        $incidence->update($validated);
                        $updated++;

                    } else {
                        Incidence::create($validated);
                        $created++;
                        $debug[] = "Fila " . ($index + 2) . ": ID {$validated['id']} creado";
                    }

                } else {

                    // NO viene ID → revisar si existe eliminado por nombre
                    $existingByName = Incidence::withTrashed()
                        ->where('name', $validated['name'])
                        ->first();

                    if ($existingByName && $existingByName->trashed()) {

                        $existingByName->restore();
                        $existingByName->update($validated);
                        $updated++;

                    } else {

                        // No existe → crear
                        Incidence::create($validated);
                        $created++;
                    }
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
            // ===== GENERAL =====
            'ID',
            'Nombre',
            'Código',
            'Categoría',
            'Tipo de incidencia',
            'Clave incapacidad',
            'Color Indicativo',
            'Descripción',

            // ===== REGLAS =====
            'Activo',
            'Solo lectura',
            'Requiere documento',
            'Requiere folio',
            'Requiere autorización',
            'Solicitada por usuario',

            // ===== CONFIG TIEMPOS =====
            'Requiere anticipación',
            'Tiempo',
            'Tipo (d, s, m)',
            'Requiere horario',
            'Requiere fecha',
            'Requiere fecha descanso',
        ];

        return response()->streamDownload(
            function () use ($headerMap) {

                // BOM UTF-8 para Excel (acentos, ñ, etc.)
                echo "\xEF\xBB\xBF";

                // Abrir salida estándar
                $handle = fopen('php://output', 'w');

                // Escribir encabezados (solo las claves visibles)
                fputcsv($handle, array_values($headerMap));

                fclose($handle);
            },
            'plantilla_importacion_incidencias.csv',
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="plantilla_importacion_departmentos.csv"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]
        );
    }

    private function uploadVideo(
        \Illuminate\Http\UploadedFile $file,
        $incidencia
    ): string {
        $disk = Storage::disk('remote_sftp_public');
        $folder = 'nominas_2/assets/videos/BIBLIOTECA DE INCIDENCIAS';
        $allowedExtensions = [
            'mp4',
            'mov',
            'webm'
        ];

        // $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'Video' => 'Archivo inválido. Extensiones permitidas: ' . implode(', ', $allowedExtensions),
            ]);
        }

        $disk->makeDirectory($folder);

        // Nombre del video
        $filename = "{$incidencia}.{$extension}";
        $remotePath = "{$folder}/{$filename}";
        $url = "https://nominas.grupo-ortiz.site/assets/videos/BIBLIOTECA DE INCIDENCIAS/{$filename}";

        if ($disk->exists($remotePath)) {
            $disk->delete($remotePath);
        }

        $ok = $disk->put($remotePath, fopen($file->getRealPath(), 'r'));

        Log::info("SFTP upload video", [
            'path' => $remotePath,
            'success' => $ok,
        ]);

        if (!$ok) {
            throw new \RuntimeException("No se pudo subir el video");
        }

        return $url;
    }




}
