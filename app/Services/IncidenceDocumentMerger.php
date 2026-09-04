<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class IncidenceDocumentMerger
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function merge(array $files): string
    {
        if ($files === []) {
            throw new RuntimeException('No se recibieron documentos para combinar.');
        }

        $this->configureWritableCache();

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        foreach ($files as $file) {
            try {
                if ($file->getMimeType() === 'application/pdf') {
                    $this->appendPdf($pdf, $file->getRealPath());
                } else {
                    $this->appendImage($pdf, $file);
                }
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    "No se pudo procesar el archivo '{$file->getClientOriginalName()}'. Verifica que no esté dañado o protegido.",
                    0,
                    $exception
                );
            }
        }

        return $pdf->Output('', 'S');
    }

    private function configureWritableCache(): void
    {
        if (defined('K_PATH_CACHE')) {
            return;
        }

        $directory = storage_path('framework/cache/tcpdf');

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el directorio temporal para combinar documentos.');
        }

        define('K_PATH_CACHE', $directory . DIRECTORY_SEPARATOR);
    }

    private function appendPdf(Fpdi $output, string $path): void
    {
        $pageCount = $output->setSourceFile($path);

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $output->importPage($page);
            $size = $output->getTemplateSize($template);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

            $output->AddPage($orientation, [$size['width'], $size['height']]);
            $output->useTemplate($template, 0, 0, $size['width'], $size['height']);
        }
    }

    private function appendImage(Fpdi $output, UploadedFile $file): void
    {
        $dimensions = getimagesize($file->getRealPath());

        if ($dimensions === false) {
            throw new RuntimeException('La imagen no es válida.');
        }

        [$pixelWidth, $pixelHeight] = $dimensions;
        $orientation = $pixelWidth > $pixelHeight ? 'L' : 'P';
        $pageWidth = $orientation === 'L' ? 297.0 : 210.0;
        $pageHeight = $orientation === 'L' ? 210.0 : 297.0;
        $margin = 10.0;
        $availableWidth = $pageWidth - ($margin * 2);
        $availableHeight = $pageHeight - ($margin * 2);
        $scale = min($availableWidth / $pixelWidth, $availableHeight / $pixelHeight);
        $width = $pixelWidth * $scale;
        $height = $pixelHeight * $scale;
        $source = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('La imagen no se pudo decodificar.');
        }

        $flattened = imagecreatetruecolor($pixelWidth, $pixelHeight);
        $white = imagecolorallocate($flattened, 255, 255, 255);
        imagefill($flattened, 0, 0, $white);
        imagecopy($flattened, $source, 0, 0, 0, 0, $pixelWidth, $pixelHeight);
        ob_start();
        imagejpeg($flattened, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($flattened);

        if ($jpeg === false) {
            throw new RuntimeException('La imagen no se pudo convertir a PDF.');
        }

        $output->AddPage($orientation, 'A4');
        $output->Image(
            '@' . $jpeg,
            ($pageWidth - $width) / 2,
            ($pageHeight - $height) / 2,
            $width,
            $height,
            'JPG',
            '',
            '',
            false,
            300
        );
    }
}
