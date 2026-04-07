<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSat;
use App\Models\InvoiceTerm;
use App\Models\InvoiceArticle;
use App\Models\InvoiceOperationType;
use App\Models\NetsuiteExpenseCategory;
use App\Models\InvoiceExclusionCategory;
use App\Models\InvoiceAccountingList;
use App\Models\InvoiceLocation;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Services\NetSuiteRestService;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Departments;
use App\Models\NetsuiteClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class XmlController
{

    public function index()
    {
        $user = auth()->user();

        $BranchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $Departments                = Departments::select('id', 'name')->get();
        $NetsuiteClass              = NetsuiteClass::select('id', 'name')->get();
        $NetsuiteExpenseCategory    = NetsuiteExpenseCategory::select('id', 'name')->get();
        $InvoiceTerm                = InvoiceTerm::select('id', 'name')->get();
        $InvoiceArticle             = InvoiceArticle::select('id', 'name')->get();
        $InvoiceOperationType       = InvoiceOperationType::select('id', 'name')->get();
        $InvoiceExclusionCategory   = InvoiceExclusionCategory::select('id', 'name')->get();
        $InvoiceAccountingList      = InvoiceAccountingList::select('id', 'name')->get();
        $InvoiceLocation            = InvoiceLocation::select('id', 'name')->get();

        return Inertia::render('XML/Index', [
            'BranchOffices'             => $BranchOffices,
            'Departments'               => $Departments,
            'NetsuiteClass'             => $NetsuiteClass,
            'NetsuiteExpenseCategory'   => $NetsuiteExpenseCategory,
            'InvoiceTerm'               => $InvoiceTerm,
            'InvoiceAccountingList'     => $InvoiceAccountingList, 
            'InvoiceArticle'            => $InvoiceArticle,
            'InvoiceExclusionCategory'  => $InvoiceExclusionCategory,
            'InvoiceLocation'           => $InvoiceLocation,
            // 'prove_gen'                 =>,
            'InvoiceOperationType'      => $InvoiceOperationType    
        ]);
    }

    // public function sendDocuments(Request $request, NetSuiteRestService $netsuite)
    // {
    //     $recordID = $request->input('recordID', 173162);
    //     $INVOICE = InvoiceSat::find($recordID);

    //     $invoice_term_id                = $request->input('invoice_term_id');
    //     $invoice_department_id          = $request->input('invoice_department_id');
    //     $invoice_class_id               = $request->input('invoice_class_id');
    //     $invoice_operation_type_id      = $request->input('invoice_operation_type_id');
    //     $invoice_location_id            = $request->input('invoice_location_id');
    //     $invoice_category_id            = $request->input('invoice_category_id');
    //     $notes                          = $request->input('notes');
    //     $invoice_article_id             = $request->input('invoice_article_id');
    //     $invoice_provider_type          = $request->input('invoice_provider_type');
    //     $invoice_exclusion_category_id  = $request->input('invoice_exclusion_category_id');
    //     $invoice_accounting_id          = $request->input('invoice_accounting_id');
    //     $order_id                       = $request->input('order_id');

    //     if (!$INVOICE) {
    //         return response()->json([
    //             'ok' => false,
    //             'message' => 'Factura no encontrada'
    //         ], 404);
    //     }

    //     $meta_xml = is_array($INVOICE->meta_xml)
    //         ? $INVOICE->meta_xml
    //         : json_decode($INVOICE->meta_xml, true);

    //     $FOLIO          = data_get($meta_xml, 'Folio', '');
    //     $REGIMEN        = data_get($meta_xml, 'Emisor.RegimenFiscal', '');
    //     $RFC            = data_get($meta_xml, 'Emisor.Rfc', '');
    //     $MONEDA         = data_get($meta_xml, 'Moneda', '');
    //     $UUID           = data_get($meta_xml, 'Complemento.TimbreFiscalDigital.UUID', '');
    //     $FECHA_XML      = data_get($meta_xml, 'Complemento.TimbreFiscalDigital.FechaTimbrado', '');
    //     $CONCEPTOS      = data_get($meta_xml, 'Conceptos.Concepto', []);
    //     $TIPO_CAMBIO    = data_get($meta_xml, 'TipoCambio', '');

    //     if (!is_array($CONCEPTOS)) {
    //         $CONCEPTOS = [];
    //     }

    //     if (isset($CONCEPTOS['ClaveProdServ'])) {
    //         $CONCEPTOS = [$CONCEPTOS];
    //     }

    //     $gastos = [];

    //     foreach ($CONCEPTOS as $concepto) {
    //         $traslados = data_get($concepto, 'Impuestos.Traslados.Traslado', []);


    //         if (is_array($traslados) && isset($traslados['Base'])) {
    //             $traslados = [$traslados];
    //         }

    //         $gastos[] = [
    //             "categoria"    => (string)$invoice_category_id, 
    //             "costo"        => data_get($concepto, 'Importe', '0'),
    //             "ubicacion"    => (string)$invoice_location_id,
    //             "departamento" => (string)$invoice_department_id,
    //             "clase"        => (string)$invoice_class_id,
    //             "concepto"     => data_get($concepto, 'Descripcion', ''),
    //             "claveprodser" => data_get($concepto, 'ClaveProdServ', ''),
    //             "Impuestos"    => [
    //                 "Traslados" => [
    //                     "Traslado" => $traslados
    //                 ]
    //             ]
    //         ];
    //     }

    //     $xmlBase64 = '';
    //     $pdfBase64 = '';

    //     if (!empty($INVOICE->xml_path)) {
    //         $xmlUrl = $INVOICE->xml_path;

    //         if (!str_starts_with($xmlUrl, 'http')) {
    //             $xmlUrl = 'https://portal-go.sfo3.cdn.digitaloceanspaces.com/sat_xml/' . ltrim($xmlUrl, '/');
    //         }

    //         $response = Http::timeout(30)->get($xmlUrl);

    //         if ($response->successful()) {
    //             $xmlBase64 = base64_encode($response->body());
    //         }
    //     }

    //     if (!empty($INVOICE->pdf_path)) {
    //         $pdfUrl = $INVOICE->pdf_path;

    //         if (!str_starts_with($pdfUrl, 'http')) {
    //             $pdfUrl = 'https://portal-go.sfo3.cdn.digitaloceanspaces.com/sat_xml/' . ltrim($pdfUrl, '/');
    //         }

    //         $response = Http::timeout(30)->get($pdfUrl);

    //         if ($response->successful()) {
    //             $pdfBase64 = base64_encode($response->body());
    //         }
    //     }

    //     $data = [
    //         "rfc"           => $RFC,
    //         "nfactura"      => $FOLIO,
    //         "regimenfiscal" => $REGIMEN,
    //         "moneda"        => $MONEDA,
    //         "termino"       => (string)$invoice_term_id,
    //         "departamento"  => (string)$invoice_department_id,
    //         "clase"         => (string)$invoice_class_id,
    //         "operacion"     => (string)$invoice_operation_type_id,
    //         "tipocambio"    => 0,
    //         "fecha"         => $FECHA_XML ? \Carbon\Carbon::parse($FECHA_XML)->format('d/m/Y') : '',
    //         "ubicacion"     => (string)$invoice_location_id,
    //         "idnetsuite"    => "",
    //         "uuid"          => $UUID,
    //         "gastos"        => $gastos,
    //         "articulos"     => [],
    //         "nota"          => "",
    //         "generico"      => "",
    //         "xml"           => $xmlBase64, 
    //         "pdf"           => $pdfBase64, 
    //     ];
    //     // return $data;

    //     try {
    //         $endpoint = '/restlet.nl?script=1963&deploy=1';
    //         $response = $netsuite->request($endpoint, 'POST', $data);

    //         $INVOICE->update([
    //             'send_status'                   => 'correct',
    //             'processing_at'                 => \Carbon\Carbon::now(),
    //             'invoice_category_id'           => $invoice_category_id,
    //             'invoice_department_id'         => $invoice_department_id,
    //             'order_id'                      => $order_id,
    //             'invoice_term_id'               => $invoice_term_id,
    //             'invoice_accounting_id'         => $invoice_accounting_id,
    //             'invoice_exclusion_category_id' => $invoice_exclusion_category_id,
    //             'invoice_article_id'            => $invoice_article_id,
    //             'invoice_provider_type'         => $invoice_provider_type,
    //             'invoice_operation_type_id'     => $invoice_operation_type_id,
    //             'invoice_class_id'              => $invoice_class_id,
    //             'invoice_location_id'           => $invoice_location_id
    //         ]);

    //         return response()->json([
    //             'ok' => true,
    //             // 'payload' => $data,
    //             'Numero_factura'    => $FOLIO,
    //             'UUID'              => $UUID,
    //             'netsuite_response' => $response,
    //         ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'ok' => false,
    //             'payload' => $data,
    //             'error' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //         ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    //     }

    // }

    public function filter_data(Request $request)
    {
        $branchOfficeIds = $request->input('branch_office_id', []);
        $departmentIds   = $request->input('department_ids', []);
        $classIds        = $request->input('class_ids', []);
        $startDate       = $request->input('start_date');
        $endDate         = $request->input('end_date');

        $query = InvoiceSat::query();

        if (!empty($branchOfficeIds) && is_array($branchOfficeIds)) {
            $query->whereIn('branch_office_id', $branchOfficeIds);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate   = Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('trandate', [$startDate, $endDate]);
        }

        if (!empty($departmentIds) && is_array($departmentIds)) {
            $query->whereIn('invoice_department_id', $departmentIds);
        }

        if (!empty($classIds) && is_array($classIds)) {
            $query->whereIn('invoice_class_id', $classIds);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function sendDocuments(NetSuiteRestService $netsuite)
    {
        $recordID = 173162;
        $INVOICE = InvoiceSat::find($recordID);

        if (!$INVOICE) {
            return response()->json([
                'ok' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        }

        $meta_xml = is_array($INVOICE->meta_xml)
            ? $INVOICE->meta_xml
            : json_decode($INVOICE->meta_xml, true);

        $FOLIO          = data_get($meta_xml, 'Folio', '');
        $REGIMEN        = data_get($meta_xml, 'Emisor.RegimenFiscal', '');
        $RFC            = data_get($meta_xml, 'Emisor.Rfc', '');
        $MONEDA         = data_get($meta_xml, 'Moneda', '');
        $UUID           = data_get($meta_xml, 'Complemento.TimbreFiscalDigital.UUID', '');
        $FECHA_XML      = data_get($meta_xml, 'Complemento.TimbreFiscalDigital.FechaTimbrado', '');
        $CONCEPTOS      = data_get($meta_xml, 'Conceptos.Concepto', []);
        $TIPO_CAMBIO    = data_get($meta_xml, 'TipoCambio', '');

        if (!is_array($CONCEPTOS)) {
            $CONCEPTOS = [];
        }

        if (isset($CONCEPTOS['ClaveProdServ'])) {
            $CONCEPTOS = [$CONCEPTOS];
        }

        $xmlBase64 = '';
        $pdfBase64 = '';

        if (!empty($INVOICE->xml_path)) {
            $xmlUrl = $INVOICE->xml_path;

            if (!str_starts_with($xmlUrl, 'http')) {
                $xmlUrl = 'https://portal-go.sfo3.cdn.digitaloceanspaces.com/sat_xml/' . ltrim($xmlUrl, '/');
            }

            $response = Http::timeout(30)->get($xmlUrl);

            if ($response->successful()) {
                $xmlBase64 = base64_encode($response->body());
            }
        }

        if (!empty($INVOICE->pdf_path)) {
            $pdfUrl = $INVOICE->pdf_path;

            if (!str_starts_with($pdfUrl, 'http')) {
                $pdfUrl = 'https://portal-go.sfo3.cdn.digitaloceanspaces.com/sat_xml/' . ltrim($pdfUrl, '/');
            }

            $response = Http::timeout(30)->get($pdfUrl);

            if ($response->successful()) {
                $pdfBase64 = base64_encode($response->body());
            }
        }

        $gastos = [];

        foreach ($CONCEPTOS as $concepto) {
            $traslados = data_get($concepto, 'Impuestos.Traslados.Traslado', []);


            if (is_array($traslados) && isset($traslados['Base'])) {
                $traslados = [$traslados];
            }

            $gastos[] = [
                "categoria"    => "64", 
                "costo"        => data_get($concepto, 'Importe', '0'),
                "ubicacion"    => "128", // fijo por ahora
                "departamento" => "105", // fijo por ahora
                "clase"        => "490", // fijo por ahora
                "concepto"     => data_get($concepto, 'Descripcion', ''),
                "claveprodser" => data_get($concepto, 'ClaveProdServ', ''),
                "Impuestos"    => [
                    "Traslados" => [
                        "Traslado" => $traslados
                    ]
                ]
            ];
        }

        $data = [
            "rfc"           => $RFC,
            "nfactura"      => $FOLIO,
            "regimenfiscal" => $REGIMEN,
            "moneda"        => $MONEDA,
            "termino"       => "4",
            "departamento"  => "105",
            "clase"         => "490",
            "operacion"     => "3",
            "tipocambio"    => 0,
            "fecha"         => $FECHA_XML ? \Carbon\Carbon::parse($FECHA_XML)->format('d/m/Y') : '',
            "ubicacion"     => "128",
            "idnetsuite"    => "",
            "uuid"          => $UUID,
            "gastos"        => $gastos,
            "articulos"     => [],
            "nota"          => "",
            "generico"      => "",
            "xml"           => $xmlBase64, 
            "pdf"           => $pdfBase64, 
        ];

        // return response()->json([
        //     'ok' => true,
        //     'payload' => $data,
        // ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        
        try {
            $endpoint = '/restlet.nl?script=1963&deploy=1';
            $response = $netsuite->request($endpoint, 'POST', $data);

            return response()->json([
                'ok' => true,
                'payload' => $data,
                'response' => $response,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'payload' => $data,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    
    }
}
