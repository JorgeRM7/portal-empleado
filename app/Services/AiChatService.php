<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiChatService
{
    protected $apiKey;
    protected $apiUrl;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key');
        $this->apiUrl = config('services.ai.api_url');
        $this->model = config('services.ai.model');
    }

    public function chat(array $messages, array $tools = [])
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.3,
        ];

        // Añadir herramientas si existen (para crear tickets, etc.)
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::withHeaders($headers)->post($this->apiUrl, $payload);

        if ($response->failed()) {
            throw new \Exception('Error en la IA: ' . $response->body());
        }

        return $response->json();
    }
}