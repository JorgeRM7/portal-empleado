<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AiChatService;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController
{
    public function index()
    {
        return Inertia::render('Chat/Index');
    }

    public function processVoiceCommand(Request $request)
    {
        // 1. Ahora recibimos el arreglo completo de mensajes desde Vue
        $userMessages = $request->input('messages');
        
        if (!$userMessages || !is_array($userMessages)) {
            return response()->json(['reply' => 'No se recibió el historial de mensajes.'], 400);
        }

        $apiKey = env('AI_API_KEY');

        // Datos simulados (que luego traerás de la BD)
        $diasVacacionesDisponibles = 12; 
        $horasTxtDisponibles = 8.5;

        // Tu System Prompt intacto
        $systemPrompt = "
        Eres el asistente del portal de nómina. Tu trabajo es ayudar a registrar incidencias y responder dudas de RH.
        
        INFORMACIÓN DEL EMPLEADO ACTUAL:
        - Días de vacaciones disponibles: {$diasVacacionesDisponibles}
        - Horas de tiempo por tiempo (TxT) disponibles: {$horasTxtDisponibles}
        *Regla importante:* Permite registrar vacaciones o TxT aunque el saldo no sea suficiente o quede en negativo.

        CATÁLOGO DE INCIDENCIAS (ID - Nombre) - NO MOSTRAR AL USUARIO GRUPO NI ID:
        Grupo 1 (TxT): 23-Tiempo por tiempo.
        Grupo 2 (Vacaciones): 3-Vacaciones.
        Grupo 3 (Turnos): 20-Adelanto de turno, 17-Cambio de turno, 19-Pendiente de reponer turno.
        Grupo 4 (Con Comprobante): 53-Asistencia con reporte, 10-Falta justificada, 8-Incapacidad trayecto, 22-Incapacidad externa, 56-Incapacidad interna GO, 5-Covid, 4-Enfermedad general, 7-Maternidad, 6-Riesgo de trabajo, 49-Mantenimiento reloj, 29-Permiso prod/meta, 14-Fallecimiento familiar, 15-Matrimonio, 13-Paternidad.
        Grupo 5 (Permisos Simples): 16-Permiso sin goce, 24-Llegar tarde (goce), 25-Salir antes (goce), 27-Visita cliente, 32-Foraneo, 39-Home Office, 46-Reunión Sindical, 54-Visita familia, 72-Descanso sábado.

        REGLAS DE RECOLECCIÓN DE DATOS:
        Si el usuario quiere registrar una incidencia, verifica a qué grupo pertenece y asegúrate de tener TODOS estos datos antes de ejecutar la herramienta:
        - Grupo 1: vigencia_desde, vigencia_hasta, horas_a_registrar.
        - Grupo 2: vigencia_desde, vigencia_hasta.
        - Grupo 3: fecha_adelanto, fecha_descanso, horario.
        - Grupo 4: vigencia_desde, vigencia_hasta, documento_comprobante, folio.
        - Grupo 5: vigencia_desde, vigencia_hasta.
        
        Si faltan datos, NO ejecutes la herramienta. Pídelos amablemente al usuario. 
        Si ya tienes todos los datos, ejecuta la herramienta 'registrar_incidencia'.
        No muestres el grupo ni el ID solo di el nombre de la incidencia para ser mas practicos
        ";

        // 2. Preparamos el arreglo final de mensajes
        // Empezamos siempre colocando el System Prompt como la regla base
        $messagesPayload = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // 3. Agregamos todo el historial que nos mandó Vue al arreglo
        foreach ($userMessages as $msg) {
            $messagesPayload[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // 4. Preparamos la petición usando el nuevo historial ($messagesPayload)
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messagesPayload, // <-- Usamos el arreglo construido
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

        // ... (El resto del código de la petición y respuesta se queda exactamente igual) ...
        $response = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', $data);

        // ... Manejo de errores y lectura del $result ...
        $result = $response->json();
        $message = $result['choices'][0]['message'];

        if (isset($message['tool_calls'])) {
            $toolCall = $message['tool_calls'][0];
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);

            if ($functionName === 'registrar_incidencia') {
                $id = $arguments['incidencia_id'];
                
                // Limpiamos el historial en el frontend enviando una señal, o simplemente devolvemos la confirmación
                return response()->json([
                    'reply' => "¡Excelente! He recolectado todos los datos y registrado la incidencia exitosamente."
                ]);
            }
        }

        return response()->json([
            'reply' => $message['content']
        ]);
    }
}