<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class FileController
{
    public function view(Request $request, string $path): StreamedResponse
    {
        return $this->serve($request, $path, false);
    }

    public function download(Request $request, string $path): StreamedResponse
    {
        return $this->serve($request, $path, true);
    }

    private function serve(Request $request, string $path, bool $download): StreamedResponse
    {
        // $disk = Storage::disk('remote_sftp');
        $isPublic = filter_var(
            $request->query('public', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $diskName = $isPublic ? 'remote_sftp_public' : 'remote_sftp';

        $disk = Storage::disk($diskName);

        // Decode URL (%2F etc)
        $path = urldecode($path);

        // Seguridad básica
        if (str_contains($path, '..')) {
            abort(403, 'Ruta inválida');
        }

        if (!$disk->exists($path)) {
            abort(404, 'Archivo no existe');
        }

        $stream = $disk->readStream($path);
        if (!$stream) {
            abort(404, 'No se pudo leer el archivo');
        }

        // Solo para visualizar algunos tipos de archivos
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mimeMap = [
            'pdf'  => 'application/pdf',

            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',

            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'webm' => 'video/webm',
        ];

        $mime = $mimeMap[$extension] ?? 'application/octet-stream';

        $filename = basename($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
