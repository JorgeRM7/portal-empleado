<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AiChatService;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChatController
{
    protected $aiService;

    public function __construct(AiChatService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'No autenticado',
                'response' => 'Por favor inicia sesión para usar el asistente.'
            ], 401);
        }
        
        $message = $request->message;
        $history = $request->history ?? [];
        
        // Obtener último ticket creado por el usuario (últimos 10 minutos)
        $lastTicket = Ticket::where('user_id', $user->id)
            ->where('created_at', '>', now()->subMinutes(10))
            ->latest()
            ->first();
        
        $lastTicketInfo = $lastTicket ? [
            'id' => $lastTicket->id,
            'subject' => $lastTicket->subject,
            'description' => substr($lastTicket->description, 0, 100),
            'created_at' => $lastTicket->created_at
        ] : null;

        // 1. Definir herramientas (Function Calling)
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'crear_ticket_soporte',
                    'description' => 'Crea un ticket de soporte técnico SOLO después de recopilar TODA la información necesaria del usuario.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'asunto' => [
                                'type' => 'string', 
                                'description' => 'Título específico (solo si el usuario te lo da). Ej: "Error al subir PDF en módulo Empleados"'
                            ],
                            'descripcion' => [
                                'type' => 'string', 
                                'description' => 'Descripción COMPLETA: módulo, acción, mensaje de error exacto, pasos para reproducir.'
                            ],
                            'es_mismo_problema' => [
                                'type' => 'boolean',
                                'description' => 'true si es el MISMO problema del ticket anterior, false si es un problema DIFERENTE'
                            ]
                        ],
                        'required' => ['asunto', 'descripcion', 'es_mismo_problema'],
                    ],
                ],
            ],
        ];

        // 2. System prompt CORREGIDO
        $lastTicketNote = $lastTicketInfo 
            ? "📋 TICKET RECIENTE: El usuario creó el ticket #{$lastTicketInfo['id']} hace poco sobre: \"{$lastTicketInfo['subject']}\". 
               • Si el usuario quiere reportar el MISMO problema: NO crees otro ticket. Dile que espere la respuesta del ticket #{$lastTicketInfo['id']}.
               • Si es un problema DIFERENTE: Sigue el proceso normal de preguntas y luego crea el ticket marcando es_mismo_problema=false."
            : "No hay tickets recientes. Sigue el proceso normal.";

        $systemPrompt = "Eres un asistente de soporte técnico para un portal de nóminas.

🔹 REGLA DE ORO: NUNCA crees un ticket sin antes hacer preguntas y recopilar información detallada.

🔹 PROCESO OBLIGATORIO (SIEMPRE):

FASE 1 - RECOPILACIÓN (OBLIGATORIA para CUALQUIER ticket):
1. Cuando el usuario mencione un error o pida crear un ticket, PRIMERO pregunta:
   • ¿En qué módulo ocurrió? (Nóminas, Empleados, Reportes, Configuración)
   • ¿Qué acción realizabas? (Subir archivo, guardar, consultar, eliminar)
   • ¿Qué mensaje de error ves exactamente? (Copia textual)
   • ¿Puedes describir los pasos para reproducirlo?

2. NO uses la herramienta 'crear_ticket_soporte' hasta tener TODOS estos datos.

FASE 2 - VERIFICAR SI ES REPETICIÓN:
3. Si hay un ticket reciente, pregunta:
   \"¿Este problema es el mismo que reportaste antes o es algo diferente?\"
   
   • Si es el MISMO: NO crees ticket. Refiere al ticket anterior.
   • Si es DIFERENTE: Continúa con la Fase 1 para el nuevo problema.

FASE 3 - CONFIRMACIÓN:
4. Resume la información y pregunta: \"¿Deseas que cree un ticket con esta información?\"
5. Solo si confirma EXPLÍCITAMENTE, usa la herramienta.

{$lastTicketNote}

🔹 EJEMPLOS CORRECTOS:

❌ INCORRECTO (NUNCA HAGAS ESTO):
Usuario: \"Quiero abrir otro ticket\"
Tú: [Crea ticket inmediatamente] ← MAL

✅ CORRECTO:
Usuario: \"Quiero abrir otro ticket\"
Tú: \"Entiendo. ¿Podrías contarme qué problema estás experimentando? ¿En qué módulo del portal ocurrió?\"
Usuario: \"En Reportes, al generar el PDF sale error 500\"
Tú: \"Gracias. ¿Qué acción exacta realizabas? ¿Era un reporte específico?\"
Usuario: \"El reporte de asistencia mensual\"
Tú: \"Perfecto. Para confirmar: error 500 al generar el reporte de asistencia mensual en el módulo Reportes. ¿Es un problema diferente al ticket anterior o es el mismo?\"
Usuario: \"Es diferente\"
Tú: [LLAMAR FUNCIÓN con es_mismo_problema=false]

🔹 NUNCA:
• Crear tickets sin información detallada
• Saltar la fase de preguntas
• Crear 2 tickets sobre el mismo problema";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...array_slice($history, -10), 
            ['role' => 'user', 'content' => $message]
        ];

        try {
            $response = $this->aiService->chat($messages, $tools);
            
            $choice = $response['choices'][0];
            $messageResponse = $choice['message'];
            $replyContent = $messageResponse['content'] ?? '';

            // Procesar tool_calls
            if (isset($messageResponse['tool_calls']) && is_array($messageResponse['tool_calls'])) {
                foreach ($messageResponse['tool_calls'] as $toolCall) {
                    
                    if ($toolCall['function']['name'] === 'crear_ticket_soporte') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        
                        $asunto = $args['asunto'] ?? '';
                        $descripcion = $args['descripcion'] ?? '';
                        $esMismoProblema = $args['es_mismo_problema'] ?? false;
                        
                        // Validar información
                        $esGenerico = (
                            strlen($asunto) < 15 || 
                            strlen($descripcion) < 30
                        );
                        
                        if ($esGenerico) {
                            Log::warning('Tool call ignorado - información insuficiente', [
                                'asunto' => $asunto,
                                'descripcion' => $descripcion
                            ]);
                            $replyContent = "Necesito más detalles para crear el ticket. ¿Podrías decirme en qué módulo ocurrió el error y qué mensaje ves exactamente?";
                        } 
                        elseif ($esMismoProblema && $lastTicketInfo) {
                            // Es el mismo problema → NO crear ticket
                            Log::info('Intento de ticket duplicado bloqueado', [
                                'user_id' => $user->id,
                                'asunto' => $asunto
                            ]);
                            $replyContent = "Veo que este es el mismo problema del ticket #{$lastTicketInfo['id']} que acabas de crear. Te sugiero esperar la respuesta del equipo técnico sobre ese ticket. Si necesitas ayuda urgente, puedes contactarnos por teléfono o email. ¿Hay algo más en lo que pueda ayudarte?";
                        } 
                        else {
                            // Ticket válido y diferente → Crear
                            $ticket = Ticket::create([
                                'user_id' => $user->id,
                                'subject' => $asunto,
                                'description' => $descripcion,
                                'status' => 'open',
                                'source' => 'chatbot',
                                'related_ticket_id' => $esMismoProblema ? $lastTicketInfo['id'] : null
                            ]);
                            
                            Log::info('Ticket creado exitosamente', [
                                'ticket_id' => $ticket->id,
                                'user_id' => $user->id,
                                'es_reincidencia' => $esMismoProblema
                            ]);
                            
                            $replyContent = "✅ He creado tu ticket de soporte con el ID #{$ticket->id}. Nuestro equipo técnico lo revisará y te contactará en breve. ¿Hay algo más en lo que pueda ayudarte?";
                        }
                    }
                }
            }

            return response()->json(['response' => $replyContent]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController: ' . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'tool call validation failed') || 
                str_contains($e->getMessage(), 'failed_generation')) {
                return response()->json([
                    'response' => "Para ayudarte mejor, necesito más información. ¿Podrías describir el problema que estás experimentando? ¿En qué módulo del portal ocurrió?"
                ]);
            }
            
            return response()->json(['error' => 'Error en el servicio de IA'], 500);    
        }
    }
}