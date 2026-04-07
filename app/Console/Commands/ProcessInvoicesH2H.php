<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\H2HDocumentsController;
use App\Services\NetSuiteRestService;
use Illuminate\Support\Facades\Log;

class ProcessInvoicesH2H extends Command
{
    protected $signature = 'process:invoices-h2h';
    protected $description = 'Ejecuta envio de comprobantes de pago a netsuite';

    public function handle(H2HDocumentsController $controller, NetSuiteRestService $netsuite)
    {
        // $this->info('Ejecutando H2H...');
        // $response = $controller->sendDocuments($netsuite);
        // $this->info(json_encode($response->getData(), JSON_PRETTY_PRINT));
        // $this->info('Proceso terminado');
        // Log::info('H2H ejecutado: ' . now());

        $this->info('Ejecutando H2H...');
        $response = $controller->sendDocuments($netsuite);
        $data = $response->getData(true);

        $total = $data['total'] ?? 0;
        $processed = $data['processed'] ?? 0;
        $skipped = $data['skipped'] ?? 0;
        $errors = $data['errors'] ?? 0;

        $this->info("Total de documentos: {$total}");
        $this->info("Documentos enviados: {$processed}");
        $this->info("Documentos omitidos: {$skipped}");
        // $this->info("Errores: {$errors}");

        // $this->info(json_encode($data, JSON_PRETTY_PRINT));

        $this->info('Proceso terminado');

        Log::info('H2H ejecutado', [
            'fecha' => now()->toDateTimeString(),
            'total' => $total,
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}