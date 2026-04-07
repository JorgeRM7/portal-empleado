<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HikVisionService
{
    public function requestInfo(string $uri, array $query = []): Response
    {



        $httpMethod = 'POST';
        $accept     = 'application/json';
        $contentType = 'application/json';
        $appKey     = config('services.hikcentral.app_key');
        $appSecret  = config('services.hikcentral.app_secret');
        $host       = rtrim(config('services.hikcentral.host'), '/');

        $uri = '/' . ltrim($uri, '/');

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        $payload = implode("\n", [
            $httpMethod,
            $accept,
            $contentType,
            'x-ca-key:' . $appKey,
            $uri,
        ]);

        $signature = base64_encode(
            hash_hmac('sha256', $payload, $appSecret, true)
        );

        $headers = [
            'Accept'                 => $accept,
            'Content-Type'           => $contentType,
            'X-Ca-Key'               => $appKey,
            'X-Ca-Signature'         => $signature,
            'X-Ca-Signature-Headers' => 'x-ca-key',
        ];

        return Http::withoutVerifying()
            ->withHeaders($headers)
            ->withBody(json_encode($query, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post("{$host}{$uri}");
    }

    public function getPersonByCode(string $personCode): array
    {
        $response = $this->requestInfo('/artemis/api/resource/v1/person/personCode/personInfo', [
            'personCode' => $personCode,
        ]);

        return [
            'response' => $response,
            'json' => $response->json(),
        ];
    }
}