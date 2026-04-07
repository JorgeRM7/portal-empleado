<?php
// test-ssl-fixed.php

echo "🔍 Diagnóstico SSL Corregido\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Verificar archivos de certificados
$caPaths = [
    'curl.cainfo' => ini_get('curl.cainfo'),
    'openssl.cafile' => ini_get('openssl.cafile'),
];

echo "📁 Archivos de certificados:\n";
foreach ($caPaths as $name => $path) {
    if ($path && file_exists($path)) {
        $size = round(filesize($path)/1024, 2) . ' KB';
        echo "   ✅ $name: $path ($size)\n";
    } else {
        echo "   ❌ $name: No configurado o no existe\n";
    }
}
echo "\n";

// 2. Fecha del sistema (CRÍTICO para SSL)
echo "📅 Fecha del sistema:\n";
$systemDate = date('Y-m-d H:i:s T');
$timestamp = time();
echo "   Sistema: $systemDate\n";
echo "   Timestamp: $timestamp\n";

// Verificar si la fecha es razonable (2024-2026)
$year = (int)date('Y');
if ($year < 2024 || $year > 2027) {
    echo "   ⚠️ ADVERTENCIA: La fecha del sistema parece incorrecta ($year)\n";
    echo "   💡 Esto puede causar errores SSL. Corrige la fecha del sistema.\n";
} else {
    echo "   ✅ Fecha dentro de rango razonable\n";
}
echo "\n";

// 3. Pruebas de conexión al SAT
echo "🌐 Pruebas de conexión al SAT:\n\n";

$testUrl = 'https://portalcfdi.facturaelectronica.sat.gob.mx/';

// Test 1: Con verificación SSL (usando enteros, no booleanos)
echo "   🔹 Test 1: Con verificación SSL (curl.cainfo):\n";
$ch = curl_init($testUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1,           // ✅ 1 en lugar de true
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    CURLOPT_SSL_VERIFYPEER => 1,           // ✅ 1 en lugar de true
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$sslResult = curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT);
curl_close($ch);

if ($errno) {
    echo "      ❌ Error ($errno): $error\n";
    echo "      💡 SSL Verify Result: $sslResult\n";
    
    // Códigos comunes de error SSL
    $sslErrors = [
        20 => 'unable to get local issuer certificate',
        21 => 'unable to verify the first certificate',
        58 => 'unable to set private key file',
        60 => 'SSL certificate problem: unable to get local issuer certificate',
    ];
    if (isset($sslErrors[$errno])) {
        echo "      📖 Significado: {$sslErrors[$errno]}\n";
    }
} else {
    echo "      ✅ Conexión exitosa. HTTP: $httpCode\n";
    echo "      📊 SSL Verify Result: $sslResult (0 = éxito)\n";
}
echo "\n";

// Test 2: Sin verificación SSL (SOLO DIAGNÓSTICO)
echo "   🔹 Test 2: Sin verificación SSL (SOLO DEBUG):\n";
$ch = curl_init($testUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    CURLOPT_SSL_VERIFYPEER => 0,           // ✅ 0 en lugar de false
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    echo "      ❌ Error ($errno): $error\n";
    echo "      💡 El problema NO es SSL (podría ser red/firewall)\n";
} else {
    echo "      ✅ Conexión exitosa. HTTP: $httpCode\n";
    echo "      💡 Confirma que el problema es la verificación SSL\n";
}
echo "\n";

// 4. Información del certificado del SAT
echo "🔐 Información del certificado del SAT:\n";
$context = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => false,
    ]
]);

$stream = @stream_socket_client("ssl://portalcfdi.facturaelectronica.sat.gob.mx:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

if ($stream) {
    $params = stream_context_get_params($stream);
    $cert = $params['options']['ssl']['peer_certificate'] ?? null;
    
    if ($cert) {
        $certInfo = openssl_x509_parse($cert);
        echo "   Emisor: " . ($certInfo['issuer']['CN'] ?? 'Desconocido') . "\n";
        echo "   Válido desde: " . date('Y-m-d', $certInfo['validFrom_time_t']) . "\n";
        echo "   Válido hasta: " . date('Y-m-d', $certInfo['validTo_time_t']) . "\n";
        
        $now = time();
        if ($now < $certInfo['validFrom_time_t']) {
            echo "   ⚠️ El certificado AÚN NO es válido (fecha del sistema incorrecta?)\n";
        } elseif ($now > $certInfo['validTo_time_t']) {
            echo "   ⚠️ El certificado HA EXPIRADO\n";
        } else {
            echo "   ✅ El certificado está vigente\n";
        }
    }
    fclose($stream);
} else {
    echo "   ❌ No se pudo conectar\n";
}