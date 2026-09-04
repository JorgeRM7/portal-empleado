<?php

namespace Tests\Unit;

use App\Services\IncidenceDocumentMerger;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tests\TestCase;

class IncidenceDocumentMergerTest extends TestCase
{
    public function test_it_combines_pdf_pages_and_an_image_into_one_pdf(): void
    {
        $cachePath = storage_path('framework/cache/tcpdf') . DIRECTORY_SEPARATOR;
        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }
        if (! defined('K_PATH_CACHE')) {
            define('K_PATH_CACHE', $cachePath);
        }

        $sourcePath = tempnam(sys_get_temp_dir(), 'incidence_pdf_');
        $imagePath = tempnam(sys_get_temp_dir(), 'incidence_image_');

        try {
            $source = new Fpdi();
            $source->setPrintHeader(false);
            $source->setPrintFooter(false);
            $source->AddPage();
            $source->Write(10, 'Página uno');
            $source->AddPage();
            $source->Write(10, 'Página dos');
            $source->Output($sourcePath, 'F');

            file_put_contents($imagePath, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            ));

            $files = [
                new UploadedFile($sourcePath, 'documento.pdf', 'application/pdf', null, true),
                new UploadedFile($imagePath, 'imagen.png', 'image/png', null, true),
            ];

            $contents = (new IncidenceDocumentMerger())->merge($files);
            $resultPath = tempnam(sys_get_temp_dir(), 'incidence_result_');
            file_put_contents($resultPath, $contents);

            $reader = new Fpdi();
            $this->assertSame(3, $reader->setSourceFile($resultPath));
            $this->assertStringStartsWith('%PDF-', $contents);

            @unlink($resultPath);
        } finally {
            @unlink($sourcePath);
            @unlink($imagePath);
        }
    }
}
