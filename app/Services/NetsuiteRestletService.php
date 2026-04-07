<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

// Clases de la librería Eher\OAuth
use Eher\OAuth\Consumer;
use Eher\OAuth\OAuthException;
use Eher\OAuth\Request;
use Eher\OAuth\Token;

// Tu clase HmacSha256 personalizada
use App\Utils\HmacSha256;

class NetsuiteRestletService
{
    protected $accountId;
    protected $consumerKey;
    protected $consumerSecret;
    protected $token;
    protected $tokenSecret;

    public function __construct()
    {
        // Cargar credenciales desde el archivo .env
        $this->accountId      = env('NETSUITE_ACCOUNT_ID');
        $this->consumerKey    = env('NETSUITE_CONSUMER_KEY');
        $this->consumerSecret = env('NETSUITE_CONSUMER_SECRET');
        $this->token          = env('NETSUITE_TOKEN_ID');
        $this->tokenSecret    = env('NETSUITE_TOKEN_SECRET');
    }

    /**
     * Realiza una llamada a un RESTlet de NetSuite.
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string $path La ruta del RESTlet (ej: '/restlet.nl?script=3326&deploy=1').
     * @param array $data Datos a enviar en el cuerpo de la solicitud (para POST/PUT).
     * @return array|null Respuesta JSON decodificada de NetSuite, o null en caso de error.
     * @throws GuzzleException Si ocurre un error en la solicitud HTTP.
     */
    public function callRestlet(string $method, string $path, array $data = [])
    {
        $client = new Client();
        // Construye la URL completa del RESTlet usando el ID de cuenta del .env
        $restlet_url = 'https://' . $this->accountId . '.restlets.api.netsuite.com/app/site/hosting' . $path;

        try {
            // Construye los encabezados de autenticación OAuth
            $headers = $this->buildHeaders($method, $restlet_url);

            $options = [
                RequestOptions::HEADERS => $headers,
            ];

            // Solo añade el cuerpo JSON para métodos que lo soportan
            if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                $options[RequestOptions::JSON] = $data;
            }

            $response = $client->request($method, $restlet_url, $options);

            // Manejo de errores HTTP (códigos 4xx o 5xx)
            if ($response->getStatusCode() >= 400) {
                // Puedes loguear el error o lanzar una excepción más específica
                //Log::error('NetSuite API Error: ' . $response->getStatusCode() . ' - ' . $response->getBody()->getContents());
                return null;
            }

            return json_decode($response->getBody()?->getContents(), true);

        } catch (OAuthException $e) {
            // Captura errores específicos de OAuth (ej. firma incorrecta)
            //\Log::error('OAuth Error: ' . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            // Captura errores de Guzzle (problemas de red, timeout, etc.)
            //\Log::error('Guzzle HTTP Error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            // Captura cualquier otra excepción inesperada
            //\Log::error('Unexpected Error in NetSuiteRestletService: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construye los encabezados de autorización OAuth 1.0a para las solicitudes a NetSuite RESTlets.
     *
     * @param string $method Método HTTP.
     * @param string $url URL completa del RESTlet.
     * @return array Array asociativo de encabezados.
     * @throws OAuthException Si la construcción de la firma OAuth falla.
     */
    protected function buildHeaders(string $method, string $url): array
    {
        // Instancia de tu método de firma personalizado
        $signature_method = new HmacSha256();
        // Instancias de Consumer y Token con credenciales del .env
        $consumer         = new Consumer($this->consumerKey, $this->consumerSecret);
        $accessToken      = new Token($this->token, $this->tokenSecret);

        // Construye el objeto Request con los parámetros OAuth estándar
        $request = new Request($method, $url, [
            'oauth_nonce'            => md5(mt_rand()), // Nonce aleatorio
            'oauth_timestamp'        => time(),         // Timestamp actual
            'oauth_version'          => '1.0',
            'oauth_token'            => $accessToken->key,
            'oauth_consumer_key'     => $consumer->key,
            'oauth_signature_method' => $signature_method->get_name(),
        ]);

        // Construye la firma OAuth
        $signature = $request->build_signature($signature_method, $consumer, $accessToken);
        $request->set_parameter('oauth_signature', $signature);
        // Establece el 'realm' con el ID de tu cuenta
        $request->set_parameter('realm', $this->accountId);

        try {
            return [
                // to_header() devuelve "OAuth realm=..., oauth_signature=...",
                // el substr(..., 15) es para quitar "OAuth " si Guzzle lo añade automáticamente,
                // o para que el formato sea solo los parámetros clave-valor.
                // Si tienes problemas, prueba a quitar el substr.
                'Authorization' => substr($request->to_header($this->accountId), 15),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];
        } catch (OAuthException $e) {
            // Relanza la excepción para que sea manejada por el método callRestlet
            throw $e;
        }
    }
}