<?php

namespace App\Http\Controllers;

use App\Notifications\TicketAssignment;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Models\EmployeeComplains;
use App\Models\EmployeeComplainsAsigments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BranchOffice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComplaintsModuleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        if (!$userId) {
            return redirect()->route('login');
        }

        // 1. Traemos el historial
        $history = EmployeeComplains::where('employee_id', $userId)
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc')
            ->get();

        $history->transform(function ($queja) {
            $files = [];

            if ($queja->path_complain && Storage::disk('public')->exists($queja->path_complain)) {
                $allFiles = Storage::disk('public')->files($queja->path_complain);
                $files = array_map(function ($file) {
                    return [
                        'name' => basename($file),
                        'url'  => asset('storage/' . $file)
                    ];
                }, $allFiles);
            }
            $queja->archivos = $files;
            return $queja;
        });

        return Inertia::render('ComplaintsModule/Index', [
            'history' => $history
        ]);
    }

    public function filter_data(Request $request)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // 🔥 Ya NO usamos mapStatus aquí
            $dataRaw = EmployeeComplains::filterComplaints([
                'employee_id' => $userId,
                'startDate'   => $request->startDate,
                'endDate'     => $request->endDate,
                'status'      => $request->status, // 👈 directo
                'subject'     => $request->subject,
            ]);

            $data = collect($dataRaw)->map(function ($queja) {

                $files = [];

                if (
                    $queja->path_complain &&
                    Storage::disk('public')->exists($queja->path_complain)
                ) {
                    $allFiles = Storage::disk('public')->files($queja->path_complain);

                    $files = array_map(function ($file) {
                        return [
                            'name' => basename($file),
                            'url'  => asset('storage/' . $file)
                        ];
                    }, $allFiles);
                }

                $queja->archivos = $files;

                return $queja;
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function improveWriting(Request $request)
    {
        $original = $request->input('text') ?? $request->input('texto');
        $asuntoCod = $request->input('asunto_cod');
        $asuntoTexto = $request->input('asunto_texto');
    
        if (!$original) {
            return response()->json(['success' => false, 'message' => 'No se recibió texto'], 400);
        }
    
        try {
            // 1. VALIDACIÓN DE LONGITUD
            if (mb_strlen($original) > 300) {
                return response()->json([
                    'success' => false,
                    'message' => 'El texto no debe exceder 300 caracteres'
                ], 400);
            }
    
            // 2. CONSTRUCCIÓN DEL PROMPT PARA GROQ
            $prompt = $this->construirPrompt($original, $asuntoCod, $asuntoTexto);
    
            // 3. LLAMADA A GROQ
            $opciones = $this->obtenerOpciones($prompt);
    
            if (!$opciones || count($opciones) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron generar sugerencias'
                ], 500);
            }
    
            return response()->json([
                'success' => true,
                'asunto' => "REPORTE DE " . mb_strtoupper($asuntoTexto ?? 'INCIDENCIA', 'UTF-8'),
                'opciones' => $opciones
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error en improveWriting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud'
            ], 500);
        }
    }
    
    /**
     * Construye el prompt para Groq de manera estratégica
     */
    private function construirPrompt($original, $asuntoCod, $asuntoTexto)
    {
        $contexto = $this->mapearContextoAsunto($asuntoCod);
        
        return <<<PROMPT
    Eres un especialista en redacción corporativa y compliance laboral. 
    Tu tarea es reescribir quejas o denuncias laborales de manera profesional, clara, objetiva y respetuosa.
    
    CONTEXTO DEL ASUNTO: $asuntoTexto
    TIPO DE REPORTE: $contexto
    
    TEXTO ORIGINAL (puede contener errores, palabras antisonantes, o ser poco claro):
    "{$original}"
    
    INSTRUCCIONES:
    1. Reescribe el texto eliminando palabras ofensivas, malsonantes o inapropiadas
    2. Corrige errores de ortografía y gramática sin cambiar el significado original
    3. Estructura la redacción de forma clara y profesional
    4. Mantén el tono formal pero accesible (apto para un documento corporativo)
    5. Asegúrate de que sea fácil de entender
    6. La redacción debe mantener la esencia del problema reportado
    7. Máximo 300 caracteres en la salida
    8. No agregues información que no esté en el texto original
    9. Habla en primera persona, como si tu estuvieras reportando el problema
    
    FORMATO DE RESPUESTA:
    Genera EXACTAMENTE 3 versiones reescritas diferentes. 
    Cada una debe tener un enfoque ligeramente distinto en la presentación.
    Responde SOLO con las 3 versiones, una por línea, sin numeración ni explicaciones adicionales.
    
    IMPORTANTE: Cada versión debe estar completa y ser independiente.
    PROMPT;
    }
    
    /**
     * Mapea el código de asunto a contexto para Groq
     */
    private function mapearContextoAsunto($asuntoCod)
    {
        $mapeo = [
            'NOM' => 'Discrepancia en nómina o cálculo de salario',
            'COM' => 'Cuestión relacionada con compensaciones o bonificaciones',
            'EXT' => 'Reclamo sobre tiempos extras o horas adicionales',
            'ACI' => 'Aclaración necesaria sobre incidencias laborales',
            'CDP' => 'Cambio en datos personales del empleado',
            'CDB' => 'Cambio en datos bancarios o información de depósito',
            'VAC' => 'Cuestión sobre vacaciones, permisos o días libres',
            'DCP' => 'Descuentos de créditos, préstamos o pensiones',
            'DVD' => 'Asunto sobre despensas o vales de despensa',
            'INC' => 'Reclamo sobre incentivos, bonos o pago anual',
            'CON' => 'Solicitud de constancias o documentación',
            'OTR' => 'Otro tipo de reporte laboral',
        ];
    
        return $mapeo[$asuntoCod] ?? 'Reporte laboral general';
    }
    
    /**
     * Realiza la llamada a Groq y procesa la respuesta
     */
    private function obtenerOpciones($prompt)
    {
        $apiUrl = env('AI_API_URL');
        $apiKey = env('AI_API_KEY');
        $model = env('AI_MODEL');
    
        if (!$apiKey) {
            throw new \Exception('Credencial AI_API_KEY no configurada');
        }
    
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($apiUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,  // Balance entre creatividad y coherencia
                'max_tokens' => 600,   // Suficiente para 3 versiones
                'top_p' => 0.95,
            ]);
    
            if (!$response->successful()) {
                Log::error('Error Groq API: ' . $response->status(), $response->json());
                throw new \Exception('Error en respuesta de Groq: ' . $response->status());
            }
    
            $data = $response->json();
            $contenido = $data['choices'][0]['message']['content'] ?? '';
    
            // Parsear las opciones separadas por saltos de línea
            $opciones = array_filter(
                array_map('trim', explode("\n", $contenido)),
                fn($linea) => !empty($linea) && mb_strlen($linea) > 10
            );
    
            // Tomar máximo 3 opciones y limpiar
            $opcionesLimpias = array_map(function ($opcion) {
                // Remover números iniciales o viñetas
                $opcion = preg_replace('/^[\d\.\-\*\s]+/', '', $opcion);
                $opcion = trim($opcion);
                
                // Asegurar que no exceda 300 caracteres
                if (mb_strlen($opcion) > 300) {
                    $opcion = mb_substr($opcion, 0, 297) . '...';
                }
                
                return $opcion;
            }, array_slice($opciones, 0, 3));
    
            return array_values(array_filter($opcionesLimpias));
    
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('RequestException Groq: ' . $e->getMessage());
            throw new \Exception('Error de conexión con Groq: ' . $e->getMessage());
        }
    }

    /**
     * Reconstruye el mensaje desde cero basándose en conceptos encontrados.
     */


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = Auth::id();

        if (!$userId) {
            return redirect()->route('login');
        }

        $history = EmployeeComplains::where('employee_id', $userId)
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('ComplaintsModule/Create', [
            'history' => $history
        ]);
    }

    // private function validarTextoConIA($texto, $asuntoCod, $asuntoTexto)
    // {
    //     $prompt = <<<PROMPT
    // Eres un validador de quejas laborales. Analiza el siguiente texto:

    // CONTEXTO: $asuntoTexto
    // TEXTO A VALIDAR: "$texto"

    // Evalúa:
    // 1. ¿Contiene lenguaje ofensivo, agresivo o inapropiado para un entorno corporativo?

    // No importa si el texto no es claro, si no tiene coherencia lógica o si podría reescribirse para ser más profesional, solo evalua si 
    // el texto contiene lenguaje ofensivo, agresivo o inapropiado para un entorno corporativo.

    // RESPONDE EXACTAMENTE en este formato JSON:
    // {
    // "apto": true|false,
    // "razon": "breve explicación",
    // "version_mejorada": "texto reescrito profesionalmente (solo si apto=false pero recuperable)"
    // }
    // PROMPT;

    //     try {
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . env('AI_API_KEY'),
    //             'Content-Type' => 'application/json',
    //         ])->timeout(15)->post(env('AI_API_URL'), [
    //             'model' => env('AI_MODEL'),
    //             'messages' => [['role' => 'user', 'content' => $prompt]],
    //             'temperature' => 0.3, // Más determinista para validación
    //             'max_tokens' => 400,
    //             'response_format' => ['type' => 'json_object'] // Si Groq lo soporta
    //         ]);

    //         if (!$response->successful()) {
    //             Log::warning('Validación IA fallida: ' . $response->status());
    //             return ['valid' => true, 'razon' => 'Validación omitida por error técnico']; // Fail-open por UX
    //         }

    //         $data = $response->json();
    //         $contenido = $data['choices'][0]['message']['content'] ?? '';
    //         $analisis = json_decode($contenido, true);

    //         return [
    //             'valid' => $analisis['apto'] ?? true,
    //             'razon' => $analisis['razon'] ?? 'Análisis no disponible',
    //             'sugerencia' => $analisis['version_mejorada'] ?? null
    //         ];

    //     } catch (\Exception $e) {
    //         Log::error('Error validando texto con IA: ' . $e->getMessage());
    //         // Fail-open: permitir guardar pero registrar advertencia
    //         return ['valid' => true, 'razon' => 'Error técnico en validación'];
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'asunto_texto' => 'required|string|max:100',
    //         'asunto_cod' => 'required|string|size:3',
    //         'archivos' => 'nullable|array|max:5',
    //         'archivos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
    //         'descripcion' => 'nullable|required_unless:asunto_cod,CON|string|min:10|max:300',
    //     ]);
    //     $textoOriginal = $validated['descripcion'];
    //     $validacionIA = $this->validarTextoConIA(
    //         $textoOriginal, 
    //         $request->input('asunto_cod'), 
    //         $request->input('asunto_texto')
    //     );

    //     // Si la IA detecta problemas y NO hay versión mejorada → rechazar
    //     if (!$validacionIA['valid']) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'La queja no cumple con los criterios de redacción profesional',
    //             'code' => 'REDACCION_INVALIDA',
    //             'detalles' => $validacionIA['razon'],
    //             'accion_sugerida' => 'Usa la herramienta "Mejorar redacción" antes de enviar'
    //         ], 422);
    //     }
    //     $employeeData = DB::table('employees')
    //         ->where('id', Auth::id())
    //         ->select('branch_office_id')
    //         ->first();

    //     if (!$employeeData) {
    //         return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
    //     }

    //     return DB::transaction(function () use ($request, $employeeData) {
    //         try {

    //             $queja = EmployeeComplains::create([
    //                 'case'             => $request->descripcion,
    //                 'subject'          => $request->asunto_texto,
    //                 'response'         => null,
    //                 'date'             => Carbon::now('America/Mexico_City')->format('Y-m-d'),
    //                 'hour'             => Carbon::now('America/Mexico_City')->format('H:i:s'),
    //                 'branch_office_id' => $employeeData->branch_office_id,
    //                 'employee_id'      => Auth::id(),
    //                 'path_complain'    => null,
    //             ]);

    //             if ($request->hasFile('archivos')) {
    //                 $folderPath = "complaints/emp_" . Auth::id() . "/q_" . $queja->id;

    //                 foreach ($request->file('archivos') as $file) {
    //                     $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
    //                 }

    //                 $queja->update([
    //                     'path_complain' => $folderPath
    //                 ]);
    //             }

    //             // 🔹 Obtener sucursal
    //                 $branchOffice = BranchOffice::find($queja->branch_office_id);

    //                 $usersRH = is_string($branchOffice->users_rh_json)
    //                     ? json_decode($branchOffice->users_rh_json, true)
    //                     : $branchOffice->users_rh_json;

    //                 if (!is_array($usersRH)) {
    //                     $usersRH = [];
    //                 }

    //                 foreach ($usersRH as $userId) {

    //                     EmployeeComplainsAsigments::create([
    //                         'employee_complain_id' => $queja->id,
    //                         'user_id'              => $userId,
    //                         'assigment_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s'),
    //                         'assigment_hour'       => Carbon::now('America/Mexico_City')->format('H:i:s'),
    //                         'type'                 => 'ASIGNACION'
    //                     ]);

    //                     $user = User::find($userId);
    //                     $user->notify(new TicketAssignment(
    //                         'Quejas',
    //                         $queja->id
    //                     ));
    //                 }

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Queja registrada con archivos correctamente',
    //                 'data'    => $queja
    //             ]);

    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Error: ' . $e->getMessage()
    //             ], 500);
    //         }
    //     });
        
    // }

    private function validarTextoConIA($texto, $asuntoCod, $asuntoTexto)
    {
        $prompt = <<<PROMPT
    Eres un filtro de contenido para un sistema de quejas laborales corporativas.

    📋 TU ÚNICA TAREA:
    Detectar si el texto contiene lenguaje OFENSIVO, AGRESIVO o INAPROPIADO o FALTAS DE ORTOGRAFÍA para un entorno profesional.

    🔍 CRITERIOS DE EVALUACIÓN:
    ✅ MARCAR COMO "apto: true" SI:
    - El texto es respetuoso
    - La redacción es informal pero no ofensiva
    - Hay falta de claridad o coherencia, pero sin lenguaje inapropiado
    - El usuario expresa frustración de manera civilizada

    ❌ MARCAR COMO "apto: false" SOLO SI:
    - Contiene insultos, groserías o palabras soeces
    - Usa lenguaje discriminatorio, amenazante o hostil
    - Incluye ataques personales hacia individuos o grupos
    - Emplea términos sexualmente explícitos o violentos
    - No cumple con los estándares de comunicación profesional
    - Hay faltas de ortografía

    ✏️ SOBRE "version_mejorada":
    - SOLO proporciónala si apto=false PERO el mensaje central es válido y recuperable
    - Reescribe eliminando/modificando únicamente las palabras ofensivas y corrigiendo faltas de ortografía
    - Mantén la esencia, contexto y propósito original de la queja
    - NO inventes detalles, NO cambies el significado, NO agregues información
    - Si el texto es irrecuperable (ej: solo contiene insultos sin sustancia), deja "version_mejorada": null

    📦 CONTEXTO DE LA QUEJA:
    Tipo de asunto: $asuntoTexto ($asuntoCod)

    📝 TEXTO A ANALIZAR:
    "$texto"

    🔐 FORMATO DE RESPUESTA (JSON PURO, SIN EXPLICACIONES ADICIONALES):
    {
    "apto": true,
    "razon": "Texto respetuoso, sin lenguaje inapropiado detectado",
    "version_mejorada": null
    }

    O, si no es apto pero recuperable:
    {
    "apto": false,
    "razon": "Contiene lenguaje ofensivo o faltas de ortografía: [palabra(s) detectada(s)]",
    "version_mejorada": "Texto reescrito profesionalmente conservando el mensaje original"
    }

    O, si no es apto e irrecuperable:
    {
    "apto": false,
    "razon": "Texto compuesto principalmente por lenguaje inapropiado sin contenido sustancial o faltas de ortografía",
    "version_mejorada": null
    }

    ⚠️ IMPORTANTE: Responde ÚNICAMENTE con el objeto JSON. Sin texto antes ni después.
    PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('AI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(env('AI_API_URL'), [
                'model' => env('AI_MODEL'),
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.2, // Más determinista para clasificación binaria
                'max_tokens' => 500,
                'response_format' => ['type' => 'json_object']
            ]);

            if (!$response->successful()) {
                Log::warning('Validación IA fallida: ' . $response->status());
                return ['valid' => true, 'razon' => 'Validación omitida por error técnico', 'sugerencia' => null];
            }

            $data = $response->json();
            $contenido = $data['choices'][0]['message']['content'] ?? '';
            
            // Limpieza defensiva: remover posibles bloques de código markdown
            $contenido = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($contenido));
            
            $analisis = json_decode($contenido, true);
            
            // Validación de estructura mínima
            if (!is_array($analisis) || !isset($analisis['apto'])) {
                Log::warning('Respuesta IA con formato inválido', ['content' => $contenido]);
                return ['valid' => true, 'razon' => 'Análisis con formato inesperado', 'sugerencia' => null];
            }

            return [
                'valid' => filter_var($analisis['apto'], FILTER_VALIDATE_BOOLEAN),
                'razon' => $analisis['razon'] ?? 'Análisis completado',
                'sugerencia' => !empty($analisis['version_mejorada']) ? trim($analisis['version_mejorada']) : null
            ];

        } catch (\Exception $e) {
            Log::error('Error validando texto con IA: ' . $e->getMessage());
            return ['valid' => true, 'razon' => 'Error técnico en validación', 'sugerencia' => null];
        }
    }

    public function store(Request $request)
    {
        // 1️⃣ Validación básica de Laravel
        $validated = $request->validate([
            'asunto_texto' => 'required|string|max:100',
            'asunto_cod' => 'required|string|size:3',
            'archivos' => 'nullable|array|max:5',
            'archivos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'descripcion' => 'nullable|required_unless:asunto_cod,CON|string|min:10|max:300',
        ]);

        $textoOriginal = $validated['descripcion'] ?? '';
        
        // 2️⃣ Validación con IA (no bloqueante)
        $validacionIA = $this->validarTextoConIA(
            $textoOriginal, 
            $request->input('asunto_cod'), 
            $request->input('asunto_texto')
        );
        
        // 3️⃣ Decidir qué texto guardar
        $textoAGuardar = $textoOriginal;
        $fueEditado = false;
        
        // Si la IA detectó problemas Y proporcionó una sugerencia → usarla
        if (!$validacionIA['valid'] && !empty($validacionIA['sugerencia'])) {
            $textoAGuardar = $validacionIA['sugerencia'];
            $fueEditado = true;
        }
        // Si no es válido pero no hay sugerencia → guardar original (fail-open)

        // 4️⃣ Obtener datos del empleado
        $employeeData = DB::table('employees')
            ->where('id', Auth::id())
            ->select('branch_office_id')
            ->first();

        if (!$employeeData) {
            return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
        }

        // 5️⃣ Guardar en transacción
        return DB::transaction(function () use (
            $request, 
            $employeeData, 
            $textoAGuardar,
            $fueEditado,
            $textoOriginal,
            $validacionIA
        ) {
            try {
                // Crear queja con el texto (original o mejorado)
                $queja = EmployeeComplains::create([
                    'case'             => $textoAGuardar,  // ← Texto final
                    'subject'          => $request->asunto_texto,
                    'response'         => null,
                    'date'             => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                    'hour'             => Carbon::now('America/Mexico_City')->format('H:i:s'),
                    'branch_office_id' => $employeeData->branch_office_id,
                    'employee_id'      => Auth::id(),
                    'path_complain'    => null,
                ]);

                // 📁 Manejo de archivos (tu lógica original)
                if ($request->hasFile('archivos')) {
                    $folderPath = "complaints/emp_" . Auth::id() . "/q_" . $queja->id;

                    foreach ($request->file('archivos') as $file) {
                        $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
                    }

                    $queja->update(['path_complain' => $folderPath]);
                }

                // 👥 Asignaciones a RRHH (tu lógica original)
                $branchOffice = BranchOffice::find($queja->branch_office_id);
                $usersRH = is_string($branchOffice->users_rh_json)
                    ? json_decode($branchOffice->users_rh_json, true)
                    : $branchOffice->users_rh_json;

                if (!is_array($usersRH)) {
                    $usersRH = [];
                }

                foreach ($usersRH as $userId) {
                    EmployeeComplainsAsigments::create([
                        'employee_complain_id' => $queja->id,
                        'user_id'              => $userId,
                        'assigment_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                        'assigment_hour'       => Carbon::now('America/Mexico_City')->format('H:i:s'),
                        'type'                 => 'ASIGNACION'
                    ]);

                    $user = User::find($userId);
                    $user->notify(new TicketAssignment('Quejas', $queja->id));
                }

                // 🔹 RESPUESTA AL USUARIO CON FEEDBACK SOBRE EDICIÓN
                $message = 'Queja registrada correctamente';
                
                if ($fueEditado) {
                    $message = 'Queja registrada. Hemos ajustado la redacción para garantizar claridad profesional.';
                }

                $response = [
                    'success' => true,
                    'message' => $message,
                    'data'    => $queja
                ];

                // Opcional: incluir detalles de la edición para mostrar en frontend
                if ($fueEditado) {
                    $response['edicion'] = [
                        'texto_original' => $textoOriginal,
                        'texto_mejorado' => $textoAGuardar,
                        'razon' => $validacionIA['razon'] ?? 'Mejora de redacción automática'
                    ];
                }

                return response()->json($response);

            } catch (\Exception $e) {
                Log::error('Error en store: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        });
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
            'descripcion' => 'required|string',
            'asunto_texto' => 'required|string',
            'archivos.*' => 'file|max:10240'
        ]);

        return DB::transaction(function () use ($request, $id) {

            $queja = EmployeeComplains::findOrFail($id);

            $queja->update([
                'case'    => $request->descripcion,
                'subject' => $request->asunto_texto,
            ]);

            $folderPath = $queja->path_complain;

            // 🔥 ELIMINAR
            if ($request->has('deleted_files')) {
                foreach ($request->deleted_files as $fileName) {
                    Storage::disk('public')->delete(
                        $folderPath . '/' . $fileName
                    );
                }
            }

            // 🔥 AGREGAR
            if ($request->hasFile('archivos')) {

                if (!$folderPath) {
                    $folderPath = "complaints/emp_" . Auth::id() . "/q_" . $queja->id;
                }

                foreach ($request->file('archivos') as $file) {

                    $fileName = time() . '_' . $file->getClientOriginalName();

                    $file->storeAs($folderPath, $fileName, 'public');
                }

                $queja->update([
                    'path_complain' => $folderPath
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Queja actualizada correctamente'
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $queja = EmployeeComplains::findOrFail($id);

        if ($queja->path_complain) {
            Storage::disk('public')->deleteDirectory($queja->path_complain);
        }

        $queja->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queja eliminada correctamente'
        ]);
    }


}
