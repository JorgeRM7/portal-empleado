<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDayVacation;
use App\Models\EmployeeIncidences;
use App\Models\Incidence;
use App\Models\Logs;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AiChatService;
use App\Models\Ticket;
use App\Models\TxT;
use App\Models\UserNomina;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ChatController
{
    public function index()
    {
        return Inertia::render('Chat/Index');
    }

    public function processVoiceCommand(Request $request)
    {
        if ($request->has('is_file_submission') && $request->hasFile('document')) {
            try {
                $arguments = json_decode($request->input('arguments'), true);
                
                // Llamamos a la función de guardado saltándonos a la IA
                $this->saveIncidenceFromAi($arguments, $request);

                return response()->json([
                    'reply' => "¡Excelente! El comprobante se ha subido correctamente y la incidencia ha quedado registrada. Tus jefes han sido notificados."
                ]);
            } catch (\Exception $e) {
                Log::error("Error IA File Store: " . $e->getMessage());
                return response()->json([
                    'reply' => "Tuve un problema al subir el archivo o guardar: " . $e->getMessage()
                ], 500);
            }
        }
        // 1. Ahora recibimos el arreglo completo de mensajes desde Vue
        $userMessages = $request->input('messages');
        
        if (!$userMessages || !is_array($userMessages)) {
            return response()->json(['reply' => 'No se recibió el historial de mensajes.'], 400);
        }

        $apiKey = env('AI_API_KEY');

        $dia_actual = Carbon::now('America/Mexico_City')->format('Y-m-d');
        $num_nomina = Auth::user()->id;

        $diasVacacionesDisponibles = EmployeeDayVacation::select('amount')->where('employee_id', $num_nomina)->where('deleted_at', null)->sum('amount'); 
        $horasTxtDisponibles = TxT::select('hours')->where('id', $num_nomina)->where('approved_at','!=', null)->where('deleted_at', null)->sum('hours');
        $nombreEmpleado = Employee::select('full_name')->where('id', $num_nomina)->first();

        $schedules = Schedules::select('id','name', 'entry_time', 'leave_time')->get();

        $systemPrompt = "
        Eres el asistente del portal de nómina. Tu trabajo es ayudar a registrar incidencias y responder dudas de RH.

        La fecha actual es: {$dia_actual}. Por favor ten en cuenta esta fecha al registrar incidencias.
        
        INFORMACIÓN DEL EMPLEADO ACTUAL:
        - Días de vacaciones disponibles: {$diasVacacionesDisponibles}
        - Horas de tiempo por tiempo (TxT) disponibles: {$horasTxtDisponibles}
        - Numero de nomina del empleado: {$num_nomina}
        - Nombre del empleado: {$nombreEmpleado->full_name}
        *Regla importante:* Permite registrar vacaciones o TxT aunque el saldo no sea suficiente o quede en negativo.

        CATÁLOGO DE INCIDENCIAS (ID - Nombre) - NO MOSTRAR AL USUARIO GRUPO NI ID:
        Grupo 1 (TxT): 23-Tiempo por tiempo.
        Grupo 2 (Vacaciones): 3-Vacaciones.
        Grupo 3 (Turnos): 20-Adelanto de turno, 17-Cambio de turno, 19-Pendiente de reponer turno.
        Grupo 4 (Con Comprobante): 53-Asistencia con reporte, 10-Falta justificada, 8-Incapacidad trayecto, 22-Incapacidad externa, 56-Incapacidad interna GO, 5-Covid, 4-Enfermedad general, 7-Maternidad, 6-Riesgo de trabajo, 49-Mantenimiento reloj, 29-Permiso prod/meta, 14-Fallecimiento familiar, 15-Matrimonio, 13-Paternidad.
        Grupo 5 (Permisos Simples): 16-Permiso sin goce, 24-Llegar tarde (goce), 25-Salir antes (goce), 27-Visita cliente, 32-Foraneo, 39-Home Office, 46-Reunión Sindical, 54-Visita familia, 72-Descanso sábado.

        REGLAS DE RECOLECCIÓN DE DATOS:
        Si el usuario quiere registrar una incidencia, verifica a qué grupo pertenece y asegúrate de tener TODOS estos datos antes de ejecutar la herramienta:
        - Grupo 1: vigencia_desde, vigencia_hasta, horas_a_registrar (en este grupo solo se puede registrar un dia a la vez, es decir, no puede haber vigencias de dias distintos).
        - Grupo 2: vigencia_desde, vigencia_hasta.
        - Grupo 3: fecha_adelanto, fecha_descanso, horario.
        - Grupo 4: vigencia_desde, vigencia_hasta, documento_comprobante, folio.
        - Grupo 5: vigencia_desde, vigencia_hasta.
        
        Si faltan datos, NO ejecutes la herramienta. Pídelos amablemente al usuario. 
        Si los datos de la incidencia estan mal. Pide al usuario que los corrija y sobreescribe los que te dio al inicio.
        Si la incidencia es del grupo 1, y el usuario quiere registrar mas de un dia, no lo hagas, dile que no se puede.
        Si la incidencia es del grupo 3, en el campo de horario dale a que seleccione uno de los siguientes turnos:
        {$schedules}
        Muestra todos los turnos, no modifiques la lista, ni omitas ningun turno.
        Si la incidencia es del grupo 3, y es pendiente de reponer turno, la fecha de adelanto debe de ser menor a la fecha de descanso
        Si la incidencia es del grupo 3, y es Adelanto de turno, la fecha de descanso debe de ser menor a la fecha de adelanto
        Si ya tienes todos los datos, ejecuta la herramienta 'registrar_incidencia'.
        No muestres el grupo ni el ID solo di el nombre de la incidencia para ser mas practicos
        ";

        $messagesPayload = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        foreach ($userMessages as $msg) {
            $messagesPayload[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messagesPayload,
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'registrar_incidencia',
                        'description' => 'Ejecuta el registro en la base de datos cuando ya se tienen todos los requisitos.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'incidencia_id' => ['type' => 'integer'],
                                'vigencia_desde' => ['type' => 'string'],
                                'vigencia_hasta' => ['type' => 'string'],
                                'horas_a_registrar' => ['type' => 'number'],
                                'fecha_adelanto' => ['type' => 'string'],
                                'fecha_descanso' => ['type' => 'string'],
                                'horario' => ['type' => 'string'],
                                'documento_comprobante' => ['type' => 'string'],
                                'folio' => ['type' => 'string']
                            ],
                            'required' => ['incidencia_id'],
                            'additionalProperties' => false
                        ]
                    ]
                ]
            ],
            'tool_choice' => 'auto'
        ];

        $response = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', $data);
        $result = $response->json();
        $message = $result['choices'][0]['message'];

        if (isset($message['tool_calls'])) {
            $toolCall = $message['tool_calls'][0];
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);

            if ($functionName === 'registrar_incidencia') {
                $incidenceId = (int) $arguments['incidencia_id'];
                $documentIncidences = [53, 10, 8, 22, 56, 5, 4, 7, 6, 49, 29, 14, 15, 13];

                $vigenciaDesde = $arguments['vigencia_desde'] ?? $arguments['fecha_adelanto'];
                $vigenciaHasta = $arguments['vigencia_hasta'] ?? $arguments['fecha_descanso'];

                $contador = EmployeeIncidences::validationIncidence($incidenceId, $num_nomina, $vigenciaDesde, $vigenciaHasta);

                if($contador > 0){
                    return response()->json([
                        'reply' => "Ya tienes una incidencia registrada en esas fechas, por favor intentalo otra vez con otras fechas."
                    ]);
                }
                // VERIFICACIÓN CLAVE: Si requiere documento y NO viene en el Request
                if (in_array($incidenceId, $documentIncidences) && !$request->hasFile('document')) {
                    return response()->json([
                        'action'    => 'require_file', // Bandera para que Vue muestre el input
                        'arguments' => $arguments,     // Devolvemos los datos para no perderlos
                        'reply'     => "Entendido. Para finalizar el registro de esta incidencia, necesito que adjuntes el documento comprobante. Por favor, súbelo en el botón que acaba de aparecer."
                    ]);
                }

                try {
                    // Pasamos el $request completo para que pueda leer el archivo si existe
                    $incidence = $this->saveIncidenceFromAi($arguments, $request);

                    return response()->json([
                        'reply' => "¡Excelente! He registrado la incidencia de " . $this->getIncidenceName($arguments['incidencia_id']) . " exitosamente. Tus jefes han sido notificados."
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error IA Store: " . $e->getMessage());
                    return response()->json([
                        'reply' => "Tuve un problema al guardar en la base de datos o subir el archivo: " . $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'reply' => $message['content']
        ]);
    }

    private function saveIncidenceFromAi(array $args, Request $request)
    {
        $incidenceId = (int) $args['incidencia_id'];
        $user = Auth::user();
        
        $employee = Employee::where('id', $user->id)->first();

        $getWeekData = function (string $date) {
            $dt = new DateTime($date);
            return [
                'week_number' => $dt->format('W'),
                'week_year'   => $dt->format('Y'),
            ];
        };

        $data = [
            'employee_id'      => $employee->id,
            'incidence_id'     => $incidenceId,
            'branch_office_id' => $employee->branch_office_id,
            'comment'          => ($args['notes'] ?? '') . " [Registrado vía Asistente de Voz]",
        ];

        $documentIncidences = [53, 10, 8, 22, 56, 5, 4, 7, 6, 49, 29, 14, 15, 13];

        if (in_array($incidenceId, $documentIncidences)) {
            $week = $getWeekData($args['vigencia_desde']);
            
            // Subida al SFTP
            $disk = Storage::disk('remote_sftp');
            $dir = 'incidences/' . date('Y/m');
            $disk->makeDirectory($dir);

            $file = $request->file('document');
            $filename = uniqid('inc_', true) . '.' . $file->getClientOriginalExtension();
            $remotePath = $dir . '/' . $filename;

            $disk->put($remotePath, file_get_contents($file->getRealPath()));

            $data = array_merge($data, $week, [
                'validity_from'   => $args['vigencia_desde'],
                'validity_to'     => $args['vigencia_hasta'],
                'days'            => $this->calculateDays($args['vigencia_desde'], $args['vigencia_hasta']),
                'file_path'       => $remotePath,
                'document_number' => $args['folio'] ?? null,
            ]);
        }

        if ($incidenceId === 23) { // TxT
            $week = $getWeekData($args['vigencia_desde']);
            $data = array_merge($data, $week, [
                'validity_from' => $args['vigencia_desde'],
                'validity_to'   => $args['vigencia_desde'],
                'hours_txt'     => $args['horas_a_registrar'] ?? 0,
                'schedule_id'   => $args['horario'] ?? null,
            ]);
        } 
        elseif ($incidenceId === 3) { // Vacaciones
            $week = $getWeekData($args['vigencia_desde']);
            $data = array_merge($data, $week, [
                'validity_from' => $args['vigencia_desde'],
                'validity_to'   => $args['vigencia_hasta'],
                'days'          => $this->calculateDays($args['vigencia_desde'], $args['vigencia_hasta']),
            ]);
        }
        elseif (in_array($incidenceId, [20, 19])) { // Turnos
            $week = $getWeekData($args['fecha_adelanto']);
            $data = array_merge($data, $week, [
                'validity_from' => $args['fecha_adelanto'],
                'validity_to'   => $args['fecha_descanso'],
                'before_date'   => $args['fecha_adelanto'],
                'rest_date'     => $args['fecha_descanso'],
                'schedule_id'   => $args['horario'],
            ]);
        }
        else { // Grupos 4 y 5 (Permisos simples)
            $week = $getWeekData($args['vigencia_desde']);
            $data = array_merge($data, $week, [
                'validity_from'    => $args['vigencia_desde'],
                'validity_to'      => $args['vigencia_hasta'],
                'days'             => $this->calculateDays($args['vigencia_desde'], $args['vigencia_hasta']),
                'document_number'  => $args['folio'] ?? null,
            ]);
        }

        // 3. Crear registro en BD
        $incidence = EmployeeIncidences::create($data);

        // 4. Notificar a los padres (Tu lógica original)
        $this->notifyEmployeeParents($employee, $incidence);

        // 5. Crear Log
        Logs::create([
            'action'          => 'INSERT',
            'user_id'         => 'ia-'.Auth::id(),
            'table_name'      => 'employee_incidences',
            'date'            => Carbon::now(),
            'relationship_id' => $incidence->id
        ]);

        return $incidence;
    }

    private function calculateDays($from, $to) {
        $d1 = new DateTime($from);
        $d2 = new DateTime($to);
        return $d1->diff($d2)->days + 1;
    }

    private function notifyEmployeeParents($employee, $incidence) {
        $parentIds = $employee->employee_parent_id ? explode(',', $employee->employee_parent_id) : [];
        foreach(array_map('trim', $parentIds) as $parentId){
            $parent = Employee::find($parentId);
            if($parent && $parent->user_id){
                $user = UserNomina::find($parent->user_id);
                if($user) {
                    $user->notify(new \App\Notifications\IncidenciaRegistrada($incidence->id, $employee->id, $employee));
                }
            }
        }
    }
    
    private function getIncidenceName($id) {
        $catalogo = Incidence::select('name')->find($id);
        return $catalogo->name;
    }
}