<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Models\EmployeeComplains;
use App\Models\EmployeeComplainsAsigments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BranchOffice;

class ComplaintsModuleController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();

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
            $userId = auth()->id();

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

        if (!$original) {
            return response()->json(['success' => false, 'message' => 'No se recibió texto'], 400);
        }

        $rawText = mb_strtolower($original, 'UTF-8');

        // 1. DETECCIÓN DE FLAGS (Gravedad)
        $flags = [
            'insultos' => preg_match('/c[a4]br[o0]n|pendej[o0a4]|idiot[a4]?|est[uú]pid[o0a4]|mierd[a4]|verg[a4]|imb[eé]cil|culer[o0a4]|maric[o0a4]|jod(er|ido)|basur[a4]|maldit[o0a4]/i', $rawText),
            'grave' => preg_match('/me agarran|me tocan|manosean|empuj|jalone|contacto f[ií]sico|nalge|pito|verga|sexo|sexual|cog(er|iendo)|acos[o]?|morbos|violar|golp(e|an|ado)|peg(ar|an|ado)|putaz|madraz|agresi[oó]n f[ií]sica|violencia/i', $rawText),
            'contacto_fisico' => preg_match('/me agarran|me tocan|manosean|empuj|jalone|contacto f[ií]sico|nalge/i', $rawText),
            'sexual' => preg_match('/pito|verga|genital|sexo|sexual|cog(er|iendo)|intim|acos[o]?|morbos|violar|tijeras/i', $rawText),
            'acoso' => preg_match('/acos(an|o|ando)|hostig(an|o|amiento)|molest(an|ia constante)|me siguen|me vigilan/i', $rawText),
            'violencia_fisica' => preg_match('/golp(e|an|ado)|peg(ar|an|ado)|putaz|madraz|agresi[oó]n f[ií]sica|violencia/i', $rawText),
            'abuso_poder' => preg_match('/jef[ea]|descont|quit[oó]|autoridad|abuso|patr[oó]n|oblig(o|a)|presionan/i', $rawText),
        ];

        // 2. DICCIONARIOS PARA CONSTRUCCIÓN
        $peticion = [
            // --- Acción e Intervención ---
            'se solicita la mediación inmediata de',
            'se requiere la presencia activa de',
            'se insta a la participación de',
            'se solicita el arbitraje de',
            'es imperativo el involucramiento de',
            'se demanda la intervención directa de',
            'se requiere que tome cartas en el asunto el área de',

            // --- Revisión y Análisis ---
            'se requiere una revisión formal por parte de',
            'se solicita someter a dictamen técnico por',
            'se pide una evaluación exhaustiva de los registros por',
            'se encomienda el análisis del caso a',
            'se solicita el cotejo de la información a través de',
            'es necesaria una auditoría de los datos por parte de',

            // --- Seguimiento y Protocolo ---
            'se espera el debido seguimiento de',
            'se solicita el acompañamiento administrativo de',
            'se busca el deslinde de responsabilidades ante',
            'se turna el presente reporte para la validación de',
            'se canaliza oficialmente la inquietud hacia',
            'se pide establecer medidas correctivas en conjunto con',

            // --- Notificación Formal ---
            'se da vista formal del incidente a',
            'se remite la documentación pertinente para revisión de',
            'se informa para los efectos conducentes al área de',
            'se pone a disposición del departamento de'
        ];

        // 3. LÓGICA DINÁMICA DE RECONSTRUCCIÓN (EL CORAZÓN DE LA "IA")
        $ideas = [];

        if ($flags['grave']) {
            $ideas[] = "se notifican incidentes que vulneran la integridad física y el respeto en el entorno laboral";
        } elseif ($flags['insultos']) {
            $ideas[] = "se reporta el uso de lenguaje inapropiado y conductas contrarias al profesionalismo";
        } else {
            // Reconstrucción dinámica basada en palabras clave
            $ideas[] = $this->reconstruirEsenciaDinamica($rawText);
        }

        // Mapeo de Asunto para la petición final
        $tipoMap = ['NOM' => 'Nómina', 'COM' => 'Compensaciones', 'EXT' => 'Tiempos extra', 'VAC' => 'Vacaciones'];
        $asuntoNombre = ($asuntoCod && $asuntoCod !== 'OTR') ? ($tipoMap[$asuntoCod] ?? "el área") : "este caso";

        $ideas[] = $this->randomItem($peticion) . " Recursos Humanos para la atención de $asuntoNombre";

        // 4. GENERACIÓN DE OPCIONES CON ESTRUCTURAS FORMALES
        $estructuras = [
            // --- Estilo Directivo y Ejecutivo (Serio) ---
            "Se formaliza el presente registro administrativo para informar que {cuerpo}.",
            "Por medio de este reporte institucional, se hace constar que {cuerpo}.",
            "Se establece un precedente documental debido a que {cuerpo}.",
            "Queda asentado para los fines que correspondan que {cuerpo}.",

            // --- Estilo Informativo / Notificación ---
            "Se comunica de manera formal y para el seguimiento preventivo que {cuerpo}.",
            "Por medio de la presente notificación electrónica, se expone que {cuerpo}.",
            "Se emite este aviso de incidencia laboral puesto que {cuerpo}.",
            "Se pone en conocimiento del área pertinente que {cuerpo}.",

            // --- Estilo de Gestión de Casos (Analítico) ---
            "Derivado de la información recibida, se identifica que {cuerpo}.",
            "Se ha activado el flujo de atención correspondiente debido a que {cuerpo}.",
            "De acuerdo con los hechos manifestados, se observa que {cuerpo}.",
            "Se presenta esta notificación bajo el protocolo de resolución indicando que {cuerpo}.",

            // --- Estilo Resumen / Sintético (Breve) ---
            "Resumen de incidencia: {cuerpo}. Se solicita proceder según el protocolo vigente.",
            "Reporte de seguimiento: {cuerpo}. Quedando a la espera de una resolución formal.",
            "Documentación de incidencia: {cuerpo}. Favor de dar la atención debida.",
            "Información relevante para el área: {cuerpo}. Se pide seguimiento administrativo."
        ];

        $opcionesFinales = [];
        $ideasUnicas = array_values(array_unique($ideas));

        foreach ($estructuras as $plantilla) {
            $cuerpo = $this->unirIdeas($ideasUnicas);
            $frase = str_replace("{cuerpo}", $cuerpo, $plantilla);

            if (mb_strlen($frase) > 300) {
                $frase = mb_substr($frase, 0, 297) . '...';
            }
            $opcionesFinales[] = $frase;
        }

        return response()->json([
            'success' => true,
            'asunto' => "REPORTE DE " . mb_strtoupper($asuntoNombre, 'UTF-8'),
            'opciones' => array_values(array_unique($opcionesFinales))
        ]);
    }

    /**
     * Reconstruye el mensaje desde cero basándose en conceptos encontrados.
     */
    private function reconstruirEsenciaDinamica($texto)
    {
        $conceptos = [
            'vacaciones' => ['vacacion', 'dias', 'descanso', 'vaca', 'mesa len'],
            'nómina'     => ['nomina', 'pago', 'sueldo', 'deposito', 'dinero'],
            'asistencia' => ['falta', 'retardo', 'checador', 'entrada', 'salida']
        ];

        $tema = "la situación laboral";
        foreach ($conceptos as $key => $sinonimos) {
            foreach ($sinonimos as $sinonimo) {
                if (str_contains($texto, $sinonimo)) { $tema = $key; break 2; }
            }
        }

        // Intenciones (El "Problema")
        $intenciones = [
            'inconsistencia' => ['no sale', 'mal', 'menos', 'error', 'incorrecto', 'mnos', 'falta', 'diferencia'],
            'aclaracion'     => ['porque', 'duda', 'saber', 'como', 'explicacion']
        ];

        $problema = "la revisión de";
        foreach ($intenciones as $key => $sinonimos) {
            foreach ($sinonimos as $sinonimo) {
                if (str_contains($texto, $sinonimo)) { $problema = $key; break 2; }
            }
        }

        // Mapeo de frases objetivas
        $frasesIA = [
            'inconsistencia' => [
                "se identifican discrepancias en el cálculo y registro de $tema",
                "existen posibles errores u omisiones en los datos referentes a $tema",
                "se observa una falta de concordancia técnica en la información de $tema",
                "se detectan anomalías en las cifras reportadas sobre $tema",
                "se presenta una diferencia significativa entre lo registrado y lo percibido en $tema",
                "la información reflejada en $tema no coincide con los parámetros esperados"
            ],
            'aclaracion' => [
                "se requiere una aclaración detallada y formal sobre $tema",
                "existen inquietudes manifestadas respecto a la gestión administrativa de $tema",
                "se solicita información adicional y precisa para comprender el estatus de $tema",
                "es necesaria una orientación específica sobre los procesos de $tema",
                "se busca resolver dudas puntuales derivadas de la reciente actualización de $tema",
                "se pide profundizar en los criterios aplicados para la determinación de $tema"
            ],
            'negligencia' => [
                "se reporta una posible falta de atención o seguimiento en las tareas de $tema",
                "se observa un cumplimiento parcial o tardío en los protocolos de $tema",
                "existen señalamientos sobre una gestión ineficaz en el área de $tema",
                "se identifica una omisión de responsabilidades vinculadas a $tema"
            ],
            'maltrato' => [
                "se describen conductas que afectan el clima laboral y la sana convivencia",
                "se manifiestan inconformidades por tratos ajenos a los valores institucionales",
                "se documentan situaciones de interacción profesional que requieren revisión ética",
                "se reportan incidencias relacionadas con la comunicación y el respeto mutuo"
            ],
            'revision' => [
                "se solicita un análisis preventivo y exhaustivo sobre $tema",
                "se requiere someter a validación técnica el proceso actual de $tema",
                "es pertinente realizar una auditoría interna sobre los movimientos de $tema",
                "se busca garantizar la transparencia en la ejecución de $tema"
            ]
        ];

        $opciones = $frasesIA[$problema] ?? ["se solicita una revisión de $tema"];
        return $this->randomItem($opciones);
    }

    private function randomItem($array)
    {
        return empty($array) ? '' : $array[array_rand($array)];
    }

    private function unirIdeas($ideas)
    {
        if (count($ideas) <= 1) return $ideas[0] ?? '';
        $conectores = ['asimismo', 'aunado a lo anterior', 'de igual forma', 'adicionalmente'];
        return $ideas[0] . ', ' . $this->randomItem($conectores) . ' ' . $ideas[1];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = auth()->id();

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $employeeData = DB::table('employees')
            ->where('id', auth()->id())
            ->select('branch_office_id')
            ->first();

        if (!$employeeData) {
            return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
        }

        return DB::transaction(function () use ($request, $employeeData) {
            try {

                $queja = EmployeeComplains::create([
                    'case'             => $request->descripcion,
                    'subject'          => $request->asunto_texto,
                    'response'         => null,
                    'date'             => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                    'hour'             => Carbon::now('America/Mexico_City')->format('H:i:s'),
                    'branch_office_id' => $employeeData->branch_office_id,
                    'employee_id'      => auth()->id(),
                    'path_complain'    => null,
                ]);

                if ($request->hasFile('archivos')) {
                    $folderPath = "complaints/emp_" . auth()->id() . "/q_" . $queja->id;

                    foreach ($request->file('archivos') as $file) {
                        $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
                    }

                    $queja->update([
                        'path_complain' => $folderPath
                    ]);
                }

                // 🔹 Obtener sucursal
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
                            'assigment_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s'),
                            'assigment_hour'       => Carbon::now('America/Mexico_City')->format('H:i:s'),
                            'type'                 => 'ASIGNACION'
                        ]);
                    }

                return response()->json([
                    'success' => true,
                    'message' => 'Queja registrada con archivos correctamente',
                    'data'    => $queja
                ]);

            } catch (\Exception $e) {
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
                    $folderPath = "complaints/emp_" . auth()->id() . "/q_" . $queja->id;
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
