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

        // 1. Traer historial de quejas
        $history = EmployeeComplains::where('employee_id', $userId)
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc')
            ->get();

        $history->transform(function ($queja) {
            $files = [];

            if ($queja->path_complain && Storage::disk('remote_sftp')->exists($queja->path_complain)) {
                
                $allFiles = Storage::disk('remote_sftp')->files($queja->path_complain);
                
                $files = array_map(function ($file) use ($queja) {
                    return [
                        'name' => basename($file),
                        'path' => $file,
                        'download_url' => 'complaints/'.$queja->id.'/files/'.basename($file),
                        'type' => $this->getFileType(basename($file)),
                        'size' => @Storage::disk('remote_sftp')->size($file)
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

    private function getFileType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $types = [
            'pdf' => 'pdf',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
            'doc' => 'word', 'docx' => 'word',
            'xls' => 'excel', 'xlsx' => 'excel',
            'txt' => 'text',
            'zip' => 'zip', 'rar' => 'zip',
        ];
        
        return $types[$extension] ?? 'file';
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
                    $queja->path_complain && Storage::disk('remote_sftp')->exists($queja->path_complain)
                ) {
                    $allFiles = Storage::disk('remote_sftp')->files($queja->path_complain);

                    $files = array_map(function ($file) use ($queja) {
                        return [
                            'name' => basename($file),
                            'url'  => 'complaints/'.$queja->id.'/files/'.basename($file)
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
    Tu tarea es reescribir quejas o denuncias laborales de manera clara, objetiva y respetuosa.

    CONTEXTO DEL ASUNTO: $asuntoTexto
    TIPO DE REPORTE: $contexto

    TEXTO ORIGINAL (puede contener errores, palabras antisonantes, o ser poco claro):
    "{$original}"

    INSTRUCCIONES:
    1. Reescribe el texto eliminando palabras ofensivas, malsonantes o inapropiadas
    2. Corrige errores de ortografía y gramática sin cambiar el significado original
    3. Estructura la redacción de forma clara y un enfoque mas coloquial, como si lo escribiera un empleado para otro empleado.
    4. Mantén el tono informal pero accesible
    5. Asegúrate de que sea fácil de entender
    6. La redacción debe mantener la esencia del problema reportado
    7. Máximo 300 caracteres en la salida
    8. No agregues información que no esté en el texto original
    9. Habla en primera persona, como si tu estuvieras reportando el problema
    10. No uses emojis y no uses mas de 300 caracteres en total.

    FORMATO DE RESPUESTA:
    Genera EXACTAMENTE 3 versiones reescritas diferentes.
    Cada una debe tener un enfoque ligeramente distinto en la presentación.
    La respuesta debe ser un tanto informal, como si lo escribiera un empleado para otro empleado.
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
                        'content' => $prompt . "\n\nResponde en máximo 300 caracteres por opción. Sé conciso."
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
    Detectar si el texto contiene lenguaje OFENSIVO, AGRESIVO o INAPROPIADO o FALTAS DE ORTOGRAFÍA para un entorno profesional. No seas tan estricto, si hay comentarios que no son del todo correctos pero no son ofensivos, marcalos como apto, hay que dejar
    que el empleado se sienta comodo al momento de redactar, no es necesario que tenga una redaccion perfecta.

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

    private function validarImagenConIA($file, $asuntoCod, $asuntoTexto)
    {
        // 🔍 Validación preliminar del archivo
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSizeMB = 4; // GPT-4o-mini recomienda <4MB para mejor procesamiento

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'valid' => false,
                'razon' => 'Formato de imagen no soportado para análisis. Usa JPG, PNG o WebP.',
                'sugerencia' => null
            ];
        }

        if ($file->getSize() > $maxSizeMB * 1024 * 1024) {
            return [
                'valid' => false,
                'razon' => "La imagen excede el tamaño máximo de {$maxSizeMB}MB para análisis.",
                'sugerencia' => null
            ];
        }

        // 🔄 Convertir imagen a base64 para la API
        $imageBase64 = 'data:' . $file->getMimeType() . ';base64,' .
                    base64_encode(file_get_contents($file->getRealPath()));

        $prompt = <<<PROMPT
    Eres un filtro de contenido visual para un sistema corporativo de quejas laborales.

    📋 TU ÚNICA TAREA:
    Analizar si una imagen adjunta a una queja es APROPIADA para un entorno profesional corporativo.

    🔍 CRITERIOS DE EVALUACIÓN:

    ✅ MARCAR COMO "apto: true" SI la imagen:
    - Muestra capturas de pantalla de errores del sistema, interfaces o mensajes de error
    - Contiene documentos laborales legítimos (nóminas, formatos, reportes - con datos sensibles pixelados)
    - Es una foto de evidencia relacionada con el entorno laboral (equipo dañado, instalaciones, etc.)
    - Muestra gráficos, tablas o datos relevantes para la queja
    - Es un screenshot de correos, chats o comunicaciones laborales pertinentes

    ❌ MARCAR COMO "apto: false" SI la imagen:
    - Contiene lenguaje ofensivo, memes inapropiados o contenido humorístico no profesional
    - Muestra contenido sexual, violento, discriminatorio o políticamente sensible
    - Incluye información personal sensible visible sin pixelar (RFC, CURP, cuentas bancarias, firmas)
    - Es irrelevante para el contexto laboral (selfies, mascotas, paisajes, comida, etc.)
    - Contiene logos o contenido de competidores con intención difamatoria
    - Muestra pantallas con datos de otros empleados sin autorización
    - Es de baja calidad, borrosa o ilegible al punto de no aportar valor

    📦 CONTEXTO DE LA QUEJA:
    Tipo de asunto: $asuntoTexto (Código: $asuntoCod)

    🔐 FORMATO DE RESPUESTA (JSON PURO, SIN EXPLICACIONES):
    {
    "apto": true,
    "razon": "Imagen relevante y profesional: muestra [descripción breve]",
    "tipo_detectado": "screenshot_error|documento_laboral|evidencia_fisica|otro",
    "advertencias": []
    }

    O, si no es apto:
    {
    "apto": false,
    "razon": "[Motivo específico: contenido inapropiado, datos sensibles visibles, irrelevante, etc.]",
    "tipo_detectado": "inapropiado|sensibilidad_datos|irrelevante|calidad_baja",
    "advertencias": ["Lista de problemas específicos detectados"]
    }

    ⚠️ IMPORTANTE:
    - Responde ÚNICAMENTE con el objeto JSON válido
    - Sé específico en "razon" pero conciso (<150 caracteres)
    - "tipo_detectado" debe ser uno de los valores enumerados
    - "advertencias" es un array, puede estar vacío
    PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('AI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(env('AI_API_URL'), [
                'model' => env('AI_MODEL'), // gpt-4o-mini
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageBase64]]
                    ]
                ]],
                'temperature' => 0.1, // Muy determinista para clasificación
                'max_tokens' => 300,
                'response_format' => ['type' => 'json_object']
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Error desconocido';
                Log::warning('Validación de imagen IA fallida: ' . $error);

                // Fail-open: si la IA falla, permitimos la imagen pero logueamos
                return [
                    'valid' => true,
                    'razon' => 'Análisis de imagen omitido por error técnico',
                    'sugerencia' => null,
                    'tipo_detectado' => 'no_analizado'
                ];
            }

            $data = $response->json();
            $contenido = $data['choices'][0]['message']['content'] ?? '';

            // Limpieza defensiva de markdown
            $contenido = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($contenido));

            $analisis = json_decode($contenido, true);

            // Validación de estructura
            if (!is_array($analisis) || !isset($analisis['apto'])) {
                Log::warning('Respuesta IA de imagen con formato inválido', [
                    'content' => substr($contenido, 0, 200)
                ]);
                return [
                    'valid' => true,
                    'razon' => 'Análisis con formato inesperado',
                    'sugerencia' => null,
                    'tipo_detectado' => 'formato_invalido'
                ];
            }

            return [
                'valid' => filter_var($analisis['apto'], FILTER_VALIDATE_BOOLEAN),
                'razon' => $analisis['razon'] ?? 'Análisis completado',
                'tipo_detectado' => $analisis['tipo_detectado'] ?? 'desconocido',
                'advertencias' => $analisis['advertencias'] ?? [],
                'sugerencia' => null // No aplicable para imágenes
            ];

        } catch (\Exception $e) {
            Log::error('Error validando imagen con IA: ' . $e->getMessage());
            return [
                'valid' => true,
                'razon' => 'Error técnico en validación de imagen',
                'sugerencia' => null,
                'tipo_detectado' => 'error_tecnico'
            ];
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

        $asuntoCod = $validated['asunto_cod'];
        $asuntoTexto = $validated['asunto_texto'];
        $textoOriginal = $validated['descripcion'] ?? '';

        // 2️⃣ Validación de TEXTO con IA (no bloqueante)
        $validacionTexto = $this->validarTextoConIA($textoOriginal, $asuntoCod, $asuntoTexto);

        // Decidir texto a guardar
        $textoAGuardar = $textoOriginal;
        $textoFueEditado = false;

        if (!$validacionTexto['valid'] && !empty($validacionTexto['sugerencia'])) {
            $textoAGuardar = $validacionTexto['sugerencia'];
            $textoFueEditado = true;
        }

        // 3️⃣ Procesar archivos: validar con IA pero subir TODOS
        $archivosParaSubir = [];
        $sensitiveContent = false;
        $archivosFlagged = []; // Para logging/auditoría

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                // Validar imágenes con IA para detectar contenido sensible
                if (str_starts_with($file->getMimeType(), 'image/')) {
                    $validacionImagen = $this->validarImagenConIA($file, $asuntoCod, $asuntoTexto);

                    // Si la IA marca la imagen como no apta, activar flag de contenido sensible
                    if (!$validacionImagen['valid']) {
                        $sensitiveContent = true;
                        $archivosFlagged[] = [
                            'nombre' => $file->getClientOriginalName(),
                            'razon' => $validacionImagen['razon'],
                            'tipo' => $validacionImagen['tipo_detectado']
                        ];

                        Log::warning('Imagen marcada como sensible por IA', [
                            'user_id' => Auth::id(),
                            'archivo' => $file->getClientOriginalName(),
                            'razon' => $validacionImagen['razon']
                        ]);
                    }
                }

                // ✅ AGREGAR TODOS LOS ARCHIVOS PARA SUBIR (válidos o no)
                $archivosParaSubir[] = [
                    'file' => $file,
                    'analisis' => str_starts_with($file->getMimeType(), 'image/')
                        ? $validacionImagen ?? ['tipo_detectado' => 'documento_no_analizado']
                        : ['tipo_detectado' => 'documento_no_analizado']
                ];
            }
        }

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
            $textoFueEditado,
            $textoOriginal,
            $validacionTexto,
            $archivosParaSubir,
            $archivosFlagged,
            $asuntoCod,
            $sensitiveContent
        ) {
            try {
                // Crear queja con el flag de contenido sensible
                $queja = EmployeeComplains::create([
                    'case'             => $textoAGuardar,
                    'subject'          => $request->asunto_texto,
                    'response'         => null,
                    'date'             => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                    'hour'             => Carbon::now('America/Mexico_City')->format('H:i:s'),
                    'branch_office_id' => $employeeData->branch_office_id,
                    'employee_id'      => Auth::id(),
                    'path_complain'    => null,
                    'sensitive_content' => $sensitiveContent,
                ]);

                if (!empty($archivosParaSubir)) {
                    $folderPath = "complaints/emp_" . Auth::id() . "/q_" . $queja->id;
                    $disk = Storage::disk('remote_sftp');

                    $disk->makeDirectory($folderPath);

                    foreach ($archivosParaSubir as $archivoInfo) {
                        $file = $archivoInfo['file'];

                        $originalName = $file->getClientOriginalName();
                        $filename = pathinfo($originalName, PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $safeFilename = preg_replace('/[^\w\.\-]/', '_', $filename) . '.' . $extension;

                        $remotePath = $folderPath . '/' . $safeFilename;

                        try {
                            $contenido = file_get_contents($file->getRealPath());

                            if ($contenido === false) {
                                throw new \Exception("No se pudo leer el archivo local: {$file->getRealPath()}");
                            }

                            $uploaded = $disk->put($remotePath, $contenido);

                            if (!$uploaded) {
                                throw new \Exception("SFTP put() retornó false");
                            }

                            if (!$disk->exists($remotePath)) {
                                throw new \Exception("El archivo no existe después de subir: {$remotePath}");
                            }

                            Log::info('✅ Archivo subido a SFTP', [
                                'user_id' => Auth::id(),
                                'queja_id' => $queja->id,
                                'archivo_original' => $originalName,
                                'archivo_remoto' => $safeFilename,
                                'ruta_remota' => $remotePath,
                                'tamaño' => strlen($contenido) . ' bytes',
                                'sensitive_flag' => $sensitiveContent ? 'YES' : 'NO'
                            ]);

                        } catch (\Exception $e) {
                            Log::error('❌ Error subiendo archivo a SFTP', [
                                'archivo' => $originalName,
                                'ruta_remota' => $remotePath,
                                'error' => $e->getMessage()
                            ]);
                            continue;
                        }
                    }

                    $queja->update(['path_complain' => $folderPath]);
                }

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

                // 🔹 CONSTRUIR RESPUESTA CON FEEDBACK
                $message = 'Queja registrada correctamente';
                $warnings = [];

                // Feedback sobre edición de texto
                if ($textoFueEditado) {
                    $warnings[] = 'Hemos ajustado la redacción para garantizar claridad profesional.';
                }

                // Feedback sobre archivos marcados como sensibles (solo informativo)
                if (!empty($archivosFlagged)) {
                    $nombres = collect($archivosFlagged)->pluck('nombre')->join(', ');
                    $warnings[] = "Se detectó contenido sensible en: {$nombres}. El equipo de RRHH revisará el caso.";
                }

                if (!empty($warnings)) {
                    $message .= ' - ' . implode(' ', $warnings);
                }

                $response = [
                    'success' => true,
                    'message' => $message,
                    'data'    => $queja
                ];

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

    public function rateResponse(Request $request)
    {
        $request->validate([
            'id_complaint' => 'required|exists:employee_complains,id',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $complaint = EmployeeComplains::findOrFail($request->id_complaint);

        if ($complaint->response === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede calificar una respuesta que no existe',
            ], 400);
        }

        $complaint->update([
            'rate' => $request->rating,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta calificada correctamente',
        ]);
    }

    public function downloadFile($complaintId, $filename)
    {
        $complaint = EmployeeComplains::findOrFail($complaintId);

        if (!$complaint->path_complain) {
            abort(404, 'No existe ruta de archivos');
        }

        $filePath = $complaint->path_complain . '/' . $filename;

        if (!Storage::disk('remote_sftp')->exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk('remote_sftp')->response($filePath);
    }


}



