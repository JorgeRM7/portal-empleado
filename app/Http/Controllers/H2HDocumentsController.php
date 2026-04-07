<?php

namespace App\Http\Controllers;

use App\Models\H2hDocument;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Services\NetSuiteRestService;
use GuzzleHttp\Client;

class H2HDocumentsController
{

    public function index()
    {

        return Inertia::render('H2H/Index', []);
    }

    public function sendDocuments( NetSuiteRestService $netsuite ){

        $totalInvoices = 0;
        $processed = 0;
        $skipped = 0; $errors = 0; 
        $today = Carbon::today();   
        // $today = Carbon::yesterday(); 
          
        // 1️⃣ Invoices con COMP hoy 
        $invoices = H2hDocument::where('category_id', 5) 
        ->whereDate('created_at', $today) 
        ->select('invoice') 
        ->distinct() ->get();


        if ($invoices->isEmpty()) { 
            return response()->json([
                'message' => 'No hay documentos por enviar',
                'total' => $totalInvoices,
                'processed' => $processed,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        }

        foreach ($invoices as $row) {
            $totalInvoices++; 
            $invoice = $row->invoice;

            // 2️⃣ DOPU 
            $DOPU = H2hDocument::where('invoice', $invoice) 
            ->where('category_id', 1) 
            ->orderByDesc('id') 
            ->first();


            // 3️⃣ COMP 
            $COMP = H2hDocument::where('invoice', $invoice) 
            ->where('category_id', 5) 
            ->orderByDesc('id') 
            ->first();

            if (!$DOPU || !$COMP) { $skipped++; continue; }


            // 4️⃣ Descargar PDF 
            $pdfUrl = "https://servicios-go.com/h2h/download/{$invoice}"; 
            $client = new Client([ 
                'verify' => false, 
                'timeout' => 60,
            ]);

            $response = $client->get($pdfUrl); 
            if ($response->getStatusCode() !== 200) { 
                $skipped++; continue; 
            }

            $pdfBase64 = base64_encode( $response->getBody()->getContents() );

            // 5️⃣ Payload NetSuite 
            $payload = [ 
                'success' => true,
                'response' => [ 
                    'action' => 'Update',
                    'update_data_register' => [ 
                        'ctrl' => [ 
                            'codigo' => [],
                            'estatus' => [],
                        ],  
                        'content' => '',
                        'unique_idenfier' => $DOPU->filename,
                        'internalid_netsuite' => $DOPU->netsuite_id,
                        'prefijo_archivo' =>
                        'COMP',
                        'filename' => $COMP->filename,
                        'srv_data' => '08_TG-0010',
                        'files' => [ 
                            'txt' => null,
                            'pdf' => $pdfBase64,
                            'xml' => null,
                        ],
                    ], 
                ], 
            ];


            // 6️⃣ Enviar a NetSuite 
            try { 
                $endpoint = config('services.netsuite.script_payment'); 
                $netsuite->request($endpoint, 'POST', $payload); 
                $processed++; 
            } catch (\Throwable $e) { 
                $errors++; continue; 
            }
        }

        return response()->json([
            'message' => 'Proceso terminado',
            'total' => $totalInvoices,
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);

    }

}
