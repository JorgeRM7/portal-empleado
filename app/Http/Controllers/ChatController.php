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

        // OBTENER Y FORMATEAR LOS HORARIOS
        $schedules = Schedules::select('id','name', 'entry_time', 'leave_time')->get();
        $schedulesList = "";
        foreach($schedules as $schedule) {
            // Creamos una lista clara para la IA en lugar de un JSON plano
            $schedulesList .= "- ID: {$schedule->id} | Turno: {$schedule->name} | Horario: {$schedule->entry_time} a {$schedule->leave_time}\n";
        }

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
        - Grupo 4: vigencia_desde, vigencia_hasta, folio.
        - Grupo 5: vigencia_desde, vigencia_hasta.
        
        Si faltan datos, NO ejecutes la herramienta. Pídelos amablemente al usuario. 
        Si los datos de la incidencia estan mal. Pide al usuario que los corrija y sobreescribe los que te dio al inicio.
        Si la incidencia es del grupo 1, y el usuario quiere registrar mas de un dia, no lo hagas, dile que no se puede.

        REGLAS DE INCAPACIDADES:
        Siempre se debe de especificar el tipo de incapacidad, es decir, si te dicen que quieren registrar una incapacidad, primero preguntas el tipo
        y despues continuas con lo demas, nunca des por hecho el tipo de incapacidad
        Si ya tienes datos de la incapacidad, no los vuelvas a pedir.
        
        REGLAS DE HORARIOS (Turnos):
        Si la incidencia requiere un horario, muestra los siguientes turnos para que el usuario elija, no edites la lista ni la resumas, muestra todo:
        {$schedulesList}
        *MUY IMPORTANTE:* Al ejecutar la herramienta 'registrar_incidencia', en el parámetro 'horario' DEBES ENVIAR ÚNICAMENTE EL NÚMERO DE ID. Jamás envíes el nombre del turno.
        
        Si la incidencia es del grupo 3, y es pendiente de reponer turno, primero descansas y despues trabajas, es decir, la fecha de descanso debe ser primero que la de adelanto.
        Si la incidencia es del grupo 3, y es Adelanto de turno, primero trabajas y después descansas, es decir, la fecha de adelanto debe ser primero que la de descanso.
        Si la incidencia es del grupo 3, y es cambio de turno, las fechas van a ser como si fuera una incidencia simple, es decir, solo pide el rango de fechas y aparte el turno al que se va a cambiar, estas fechas van a ser los campos vigencia_desde y vigencia_hasta 
        SI TE PREGUNTAN POR EL CATALOGO DE INCIDENCIAS O ALGO RELACIONADO CON ESO RESPONDE CON EL CATALOGO QUE TIENES CARGADO SIN DECIR A QUE GRUPO PERTENECE CADA INCIDENCIA Y SIN RESUMIR, DA EL CATALOGO COMPLETO
        Si te dan solo una fecha, pregunta que si quiere usar esa fecha para ambos casos, de inicio o de fin
        
        *IMPORTANTE: SOLO PUEDES REGISTRAR INCIDENCIAS DONDE SUS FECHAS ESTEN DENTRO DE LA SEMANA ACTUAL, TOMA EN CUENTA QUE EL DIA DE HOY ES {$dia_actual}, Y TOMA EN CUENTA QUE LA SEMANA VA DEL LUNES AL DOMINGO*
        Datos que ya tengas, no los pidas repetitivamente, solo una vez es suficiente
        Si ya tienes todos los datos, primero pregunta si esta todo correcto, si la respuesta es afirmativa,ejecuta la herramienta 'registrar_incidencia'.
        No muestres el grupo ni el ID solo di el nombre de la incidencia para ser mas practicos.
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
                                // CAMBIO CLAVE AQUÍ: De 'string' a 'integer'
                                'horario' => [
                                    'type' => 'integer', 
                                    'description' => 'El ID numérico del horario seleccionado. Solo el número.'
                                ],
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

                $vigenciaDesde = $arguments['vigencia_desde'] ?? ($arguments['fecha_adelanto'] ?? null);
                $vigenciaHasta = $arguments['vigencia_hasta'] ?? ($arguments['fecha_descanso'] ?? null);

                // Solo validamos si tenemos fechas válidas
                if ($vigenciaDesde && $vigenciaHasta) {
                    $contador = EmployeeIncidences::validationIncidence($incidenceId, $num_nomina, $vigenciaDesde, $vigenciaHasta);

                    if($contador > 0){
                        return response()->json([
                            'reply' => "Ya tienes una incidencia registrada en esas fechas, por favor intentalo otra vez con otras fechas."
                        ]);
                    }
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
        elseif ($incidenceId === 23) { // TxT
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
        elseif (in_array($incidenceId, [20, 19])) {
            $week = $getWeekData($args['fecha_adelanto'] ?? $args['vigencia_desde'] ?? date('Y-m-d'));
            $data = array_merge($data, $week, [
                'validity_from' => $args['fecha_adelanto'] ?? null,
                'validity_to'   => $args['fecha_descanso'] ?? null,
                'before_date'   => $args['fecha_adelanto'] ?? null,
                'rest_date'     => $args['fecha_descanso'] ?? null,
                'schedule_id'   => $args['horario'] ?? null,
            ]);
        }
        elseif (in_array($incidenceId, [17])) {
            $week = $getWeekData($args['fecha_adelanto'] ?? $args['vigencia_desde'] ?? date('Y-m-d'));
            $data = array_merge($data, $week, [
                'validity_from' => $args['vigencia_desde'] ?? null,
                'validity_to'   => $args['vigencia_hasta'] ?? null,
                'schedule_id'   => $args['horario'] ?? null,
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

        // 4. Notificar a los padres
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
        if($d1->diff($d2)->days + 1 == 0){
            return null;
        }
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