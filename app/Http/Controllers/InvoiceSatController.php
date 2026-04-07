<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\InvoiceAccountingList;
use App\Models\InvoiceArticles;
use App\Models\InvoiceCategory;
use App\Models\InvoiceClass;
use App\Models\InvoiceDepartment;
use App\Models\InvoiceExclusionCategory;
use App\Models\InvoiceLocation;
use App\Models\InvoiceSat;
use App\Models\InvoiceTerm;
use App\Models\InvoiceOperationTypes;
use App\Models\Planta;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\NetSuiteRestService;
use App\Support\SatCredentialLoader;
use CfdiUtils\Nodes\XmlNodeUtils;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpCfdi\CfdiCleaner\Cleaner;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\CfdiSatScraper\Contracts\ResourceDownloadHandlerInterface;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadError;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadRequestExceptionError;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadResponseError;
use PhpCfdi\CfdiSatScraper\Filters\DownloadType as ScrapperDownloadType;
use PhpCfdi\CfdiSatScraper\Filters\Options\StatesVoucherOption;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\CfdiToJson\JsonConverter;
use PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder;
use PhpCfdi\CfdiToPdf\CfdiDataBuilder;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\CfdiToPdf\Converter;

class InvoiceSatController
{

    private NetSuiteRestService $netSuiteRestService;
    public function __construct(NetSuiteRestService $netSuiteRestService)
    {
        $this->netSuiteRestService = $netSuiteRestService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branches = BranchOffice::select([
            'branch_offices.id',
            'branch_offices.name',
            'branch_offices.code',
        ])->get();
        return Inertia::render('InvoiceSat/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InvoiceSat $invoiceSat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InvoiceSat $invoiceSat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvoiceSat $invoiceSat)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceSat $invoiceSat)
    {
        //
    }

    public function dailyChunks(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $chunks = [];
        $cur = $start->setTime(0, 0, 0);
        while ($cur <= $end) {
            $a = $cur;
            $b = $cur->setTime(23, 59, 59);
            if ($b > $end) $b = $end;
            $chunks[] = [$a, $b];
            $cur = $b->modify('+1 second'); // evita misma ventana exacta (cuota 5002)
        }
        return $chunks;
    }

    

    public function sendNetsuite(Request $request, InvoiceSat $invoiceSat)
    {
        $meta_x = $invoiceSat->meta_xml;

        $costs = map_xml_costs(data_get($meta_x, 'Conceptos.Concepto'), $invoiceSat);

        //return $costs;

        $folio = data_get($meta_x, 'Folio');

        if (is_null($folio)) {
            $folio = Str::limit(data_get($meta_x, 'Complemento.TimbreFiscalDigital.UUID'), 5, '');
        }

        $xmlPath = $invoiceSat->xml_path;
        $pdfPath = $invoiceSat->pdf_path; 

        $xmlBase64 = null;
        $pdfBase64 = null;

        if (Storage::disk('spaces')->exists('sat_xml/'.$xmlPath)) {
            $xmlContent = Storage::disk('spaces')->get('sat_xml/'.$xmlPath);
            $xmlBase64 = base64_encode($xmlContent);
        }

        if (Storage::disk('spaces')->exists('sat_xml/'.$pdfPath)) {
            $pdfContent = Storage::disk('spaces')->get('sat_xml/'.$pdfPath);
            $pdfBase64 = base64_encode($pdfContent);
        }

        $data = [
            'rfc'           => $invoiceSat->emisor_rfc,
            'nfactura'      => $folio,
            'regimenfiscal' => data_get($meta_x, 'Emisor.RegimenFiscal'),
            'moneda'        => data_get($meta_x, 'Moneda'),
            'termino'       => $invoiceSat?->invoice_term_id ?? '',
            'departamento'  => $invoiceSat?->invoice_department_id ?? '',
            'clase'         => 1,
            'operacion'     => 1,
            'tipocambio'    => $invoiceSat?->exchange_rate,
            //            'fecha'         => Carbon::parse(data_get($meta_xml, 'Complemento.TimbreFiscalDigital.FechaTimbrado'))->format('d/m/Y'),
            'fecha'      => Carbon::parse($invoiceSat->trandate)->format('d/m/Y'),
            'ubicacion'  => $invoiceSat?->invoice_location_id ?? '',
            'idnetsuite' => '',
            'uuid'       => $invoiceSat->uuid,
            'gastos'     => $costs,
            'articulos'  => [],
            'nota'       => $invoiceSat->notes                 ??= '',
            'generico'   => $invoiceSat->invoice_provider_type ??= '',
            'xml'        => $xmlBase64,
            'pdf'        => $pdfBase64,
        ];

        //return $data;

        if ($invoiceSat->invoice_article_id || $invoiceSat->invoice_accounting_id) {
            $articles          = map_xml_articles(data_get($meta_x, 'Conceptos.Concepto'), $invoiceSat);
            $data['articulos'] = $articles;
            $data['gastos']    = [];
        }

        $restletPath = '/restlet.nl?script=1908&deploy=1';

        //return $data;
        
        try {
            $response = $this->netSuiteRestService->request($restletPath, 'POST', $data);
            return response()->json(['ok' => true, 'response' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        

        
    }


    public function descargaSat(Request $request)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');
        
        $send = function($message) {
            echo "data: " . json_encode([
                'type' => 'log',
                'message' => $message,
                'timestamp' => now()->format('H:i:s')
            ]) . "\n\n";
            @ob_flush();
            @flush();
            usleep(10000);
        };

        $send("🚀 Iniciando descarga SAT...");

        set_time_limit(3000);
        ini_set('max_execution_time', '300');
        $dateFrom = $request->query('date_from'); 
        $dateTo = $request->query('date_to'); 
        $timezone = 'America/Mexico_City';

        if ($dateFrom) {
            $fechaHoraCero = Carbon::createFromFormat('Y-m-d', $dateFrom, $timezone)
                ->startOfDay();
        } else {
            $fechaHoraCero = Carbon::today($timezone)->startOfDay();
        }
        
        if ($dateTo) {
            $fechaHoraActual = Carbon::createFromFormat('Y-m-d', $dateTo, $timezone)
                ->endOfDay();
        } else {
            $fechaHoraActual = Carbon::now($timezone)->endOfDay();
        }
        

        if($request->branch_id != null){
            $brancOffice = BranchOffice::select([
                'branch_offices.id',
                'branch_office_fiscal_keys.key_path',
                'branch_office_fiscal_keys.certificate_path',
                'branch_office_fiscal_keys.passphrase',
                'branch_offices.internal_code',
                'invoice_companies.id as invoice_company_id',
                'branch_offices.code'
                ])
                ->join('branch_office_fiscal_keys', 'branch_office_fiscal_keys.branch_office_id', '=', 'branch_offices.id')
                ->join('invoice_companies', 'invoice_companies.code', '=', 'branch_offices.internal_code')
                ->whereNotNull('branch_office_fiscal_keys.key_path')
                ->whereNotNull('branch_office_fiscal_keys.certificate_path')
                ->whereNotNull('branch_office_fiscal_keys.passphrase')
                ->where('branch_offices.id', $request->branch_id)
                ->get();
        }else{
            $brancOffice = BranchOffice::select([
                'branch_offices.id',
                'branch_office_fiscal_keys.key_path',
                'branch_office_fiscal_keys.certificate_path',
                'branch_office_fiscal_keys.passphrase',
                'branch_offices.company_id',
                'branch_offices.internal_code',
                'invoice_companies.id as invoice_company_id',
                'branch_offices.code'
                ])
                ->join('branch_office_fiscal_keys', 'branch_office_fiscal_keys.branch_office_id', '=', 'branch_offices.id')
                ->join('invoice_companies', 'invoice_companies.code', '=', 'branch_offices.internal_code')
                ->whereNotNull('branch_office_fiscal_keys.key_path')
                ->whereNotNull('branch_office_fiscal_keys.certificate_path')
                ->whereNotNull('branch_office_fiscal_keys.passphrase')
                ->get();
        }

        foreach ($brancOffice as $branch) {
            echo "Procesando branch {$branch->id}...\n";
            $send("🔄 Procesando planta {$branch->code}");
            $cerKey = 'fiscal_keys/'.$branch->certificate_path;
            $keyKey = 'fiscal_keys/'.$branch->key_path;

            // 1) Verifica que existen y tienen contenido (>0 bytes)
            $spaces = Storage::disk('spaces');
            if (! $spaces->exists($cerKey) || ! $spaces->exists($keyKey)) {
                Log::warning("Faltan archivos CER/KEY en Spaces para branch {$branch->branch_id}");
                $send("⚠️  Faltan archivos CER/KEY en Spaces para planta {$branch->code}");
                continue;
            }
            if ($spaces->size($cerKey) <= 0 || $spaces->size($keyKey) <= 0) {
                Log::warning("CER/KEY vacío para branch {$branch->branch_id}");
                $send("⚠️  CER/KEY vacío para planta {$branch->code}");
                continue;
            }

            // 2) Lee contenidos y normaliza passphrase
            $cerContents = $spaces->get($cerKey);
            $keyContents = $spaces->get($keyKey);
            $pass = trim($branch->passphrase);

            $cookiePath = storage_path("app/sat_cookies/cookies.json");
            if (file_exists($cookiePath)) {
                @unlink($cookiePath); // fuerza sesión nueva
            }
            if (! is_dir(dirname($cookiePath))) {
                mkdir(dirname($cookiePath), 0777, true);
            }

            try {
                $tmpDir = storage_path('app/tmp_fiel');
                if (! is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
                $cerPath = $tmpDir.'/test.cer';
                $keyPath = $tmpDir.'/test.key';
                file_put_contents($cerPath, $cerContents);
                file_put_contents($keyPath, $keyContents);

                // 2) Intenta construir la Credential completa: aquí sabremos si pass/pareja son correctos
                $credential = \PhpCfdi\Credentials\Credential::openFiles($cerPath, $keyPath, $pass);
                $send("✅ Credencial FIEL cargada para planta {$branch->code}");
                $cert = $credential->certificate();
            } catch (\Throwable $e) {
                // Contraseña incorrecta, llave no pertenece al cert, formato inválido, etc.
                Log::error('FIEL inválida: '.$e->getMessage());
                echo "FIEL inválida para branch {$branch->code}: {$e->getMessage()}\n";
                $send("❌ FIEL inválida para planta {$branch->code}: " . $e->getMessage());
                continue;
            }

            $cookieJar = new FileCookieJar(storage_path('app/sat_cookies/cookies.json'), true);
            $client = new Client([
                    'timeout'         => 300,
                    'connect_timeout' => 30,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) SATScraper',
                    ],
                    'curl' => [
                        CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
                        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CAINFO => base_path('certificates/cacert.pem'),
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                    ],
                    'verify' => base_path('certificates/cacert.pem')
                ]);

            $gateway   = new SatHttpGateway($client, $cookieJar);

            $satScraper = new SatScraper(FielSessionManager::create($credential), $gateway);
            
            $from = new DateTimeImmutable($fechaHoraCero, new DateTimeZone('America/Mexico_City'));
            $to   = new DateTimeImmutable($fechaHoraActual, new DateTimeZone('America/Mexico_City'));

            $send("🔍 Consultando CFDI del {$from->format('Y-m-d H:i')} al {$to->format('Y-m-d H:i')}...");

            $query = new QueryByFilters($from, $to);
            $query
                ->setDownloadType(ScrapperDownloadType::recibidos())
                ->setStateVoucher(StatesVoucherOption::vigentes());

            $list = $satScraper->listByDateTime($query);

            echo "Encontrados {$list->count()} CFDI(s) para branch {$branch->code} desde {$from->format('Y-m-d H:i:s')} hasta {$to->format('Y-m-d H:i:s')}\n";
            $send("📦 Encontrados {$list->count()} CFDI(s) para planta {$branch->code}");    
            $dest = storage_path('app/sat_xml');
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }

            $downloadedUuids = $satScraper
                ->resourceDownloader(ResourceType::xml(), $list, 20)
                ->saveTo($dest, true, 0777);
            
            
            $send("⬇️  Descargados " . count($downloadedUuids) . " CFDI(s)");
                
            foreach ($downloadedUuids as $uuid) {
                try {
                    $uuidUpper = Str::upper($uuid);
                    $remote = "sat_xml/{$uuidUpper}.xml";
                    $remotePdf = "sat_xml/{$uuidUpper}.pdf";
                    if (Storage::disk('spaces')->exists($remote)) {
                        echo "El archivo {$remote} ya existe en Spaces.\n";
                        $send("⚠️  El archivo {$remote} ya existe.");
                        continue; // ya existe: no subas duplicado
                    }
                    $local = "{$dest}/{$uuid}.xml";
                    // Sube por stream (eficiente)
                    Storage::disk('spaces')->put($remote, fopen($local, 'r'));

                
                    $xmlPath = "{$dest}/{$uuid}.xml";
                    if (!file_exists($xmlPath)) {
                        Log::warning("XML no encontrado para {$uuid}");
                        $send("⚠️  XML no encontrado para {$uuid}");
                        continue;
                    }

                    if (Storage::disk('spaces')->exists($remotePdf)) {
                        echo "El archivo {$remotePdf} ya existe.\n";
                        $send("⚠️  El archivo {$remotePdf} ya existe.");
                        continue; // ya existe: no subas duplicado
                    }
                    

                    $xml = file_get_contents($xmlPath);
                    $xml = Cleaner::staticClean($xml);
                    $pdfPath = "{$dest}/{$uuid}.pdf";

                    $comprobante = XmlNodeUtils::nodeFromXmlString($xml);
                    $cfdiData    = (new CfdiDataBuilder())->build($comprobante);
                    (new Converter(new Html2PdfBuilder()))
                        ->createPdfAs($cfdiData, $pdfPath);
                    $converter = new Converter(new Html2PdfBuilder());

                    
                    $converter->createPdfAs($cfdiData, $pdfPath);

                    Storage::disk('spaces')->put($remotePdf, fopen($pdfPath, 'r'));

                    $array = json_decode(JsonConverter::convertToJson($xml), true);

                    $invoice_id         = '';
                    $emisor_name        = data_get($array, 'Emisor.Nombre');
                    $emisor_rfc         = data_get($array, 'Emisor.Rfc');
                    $trandate           = data_get($array, 'Fecha');
                    $total              = data_get($array, 'Total');
                    $status             = '1';
                    $subsidiary         = data_get($array, 'Receptor.Nombre');
                    $efecto_comprobante = data_get($array, 'TipoDeComprobante');
                    $trandate_cer       = data_get($array, 'Complemento.TimbreFiscalDigital.FechaTimbrado');
                    $trandate_cancel    = data_get($array, 'Complemento.TimbreFiscalDigital.FechaCancelacion');

                    echo json_encode([
                        'uuid'               => $uuidUpper,
                        'invoice_id'         => $invoice_id,
                        'emisor_name'        => $emisor_name,
                        'emisor_rfc'         => $emisor_rfc,
                        'trandate'           => $trandate,
                        'total'              => $total,
                        'status'             => $status,
                        'subsidiary'         => $subsidiary,
                        'efecto_comprobante' => $efecto_comprobante,
                        'rfc_pac'            => null,
                        'trandate_cer'       => $trandate_cer,
                        'trandate_cancel'    => $trandate_cancel,
                        'meta_xml'          => null,
                        'xml_path'          => "{$uuid}.xml",
                        'pdf_path'          => "{$uuid}.pdf",
                        'branch_office_id'   => $branch->id,
                        'invoice_company_id' => $branch->invoice_company_id,
                    ])."\n";

                    

                    $invoice = InvoiceSat::create(
                        [
                            'uuid'               => $uuidUpper,
                            'invoice_id'         => $invoice_id,
                            'emisor_name'        => $emisor_name,
                            'emisor_rfc'         => $emisor_rfc,
                            'trandate'           => $trandate,
                            'total'              => $total,
                            'status'             => $status,
                            'subsidiary'         => $subsidiary,
                            'efecto_comprobante' => $efecto_comprobante,
                            'rfc_pac'            => null,
                            'trandate_cer'       => $trandate_cer,
                            'trandate_cancel'    => $trandate_cancel,
                            'meta_xml'          => $array,
                            'xml_path'          => "{$uuidUpper}.xml",
                            'pdf_path'          => "{$uuidUpper}.pdf",
                            'branch_office_id'   => $branch->id,
                            'invoice_company_id' => $branch->invoice_company_id,
                        ]
                    );

                    $send("📦 Nueva factura creada: {$invoice->id} - {$invoice->emisor_rfc} - {$invoice->emisor_name} - {$invoice->total}");
                    

                } catch (\Throwable $e) {
                    Log::warning("Fallo generando PDF {$uuid}: {$e->getMessage()}");
                    $send("❌ Error en planta {$branch->code}: " . $e->getMessage());
                    continue;
                }

                
            }
            $send("✅ Planta {$branch->code} completado");
            usleep(300 * 1000); // 300ms entre branches (no es necesario, pero por si acaso)
        }

        $send("🎉 Proceso completado exitosamente");
    
        // Señal de fin para el frontend
        echo "data: " . json_encode(['type' => 'complete', 'timestamp' => now()->format('H:i:s')]) . "\n\n";
        @ob_flush();
        @flush();
        
        return response()->stream(function() {}, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
        
    }

     public function descargaSatTest(Request $request)
    {


        set_time_limit(3000);
        ini_set('max_execution_time', '300');

        $fechaHoraActual = Carbon::now()->format('Y-m-d H:i:s');
        $fechaHoraCero = Carbon::today()->format('Y-m-d H:i:s');

        $brancOffice = BranchOffice::select([
            'branch_offices.id',
            'branch_office_fiscal_keys.key_path',
            'branch_office_fiscal_keys.certificate_path',
            'branch_office_fiscal_keys.passphrase',
            'branch_offices.internal_code',
            'invoice_companies.id as invoice_company_id',
            ])
            ->join('branch_office_fiscal_keys', 'branch_office_fiscal_keys.branch_office_id', '=', 'branch_offices.id')
            ->join('invoice_companies', 'invoice_companies.code', '=', 'branch_offices.internal_code')
            ->whereNotNull('branch_office_fiscal_keys.key_path')
            ->whereNotNull('branch_office_fiscal_keys.certificate_path')
            ->whereNotNull('branch_office_fiscal_keys.passphrase')
            ->get();

        foreach ($brancOffice as $branch) {
            echo "Procesando branch {$branch->id}...\n";
            $cerKey = 'fiscal_keys/'.$branch->certificate_path;
            $keyKey = 'fiscal_keys/'.$branch->key_path;

            // 1) Verifica que existen y tienen contenido (>0 bytes)
            $spaces = Storage::disk('spaces');
            if (! $spaces->exists($cerKey) || ! $spaces->exists($keyKey)) {
                Log::warning("Faltan archivos CER/KEY en Spaces para branch {$branch->branch_id}");
                continue;
            }
            if ($spaces->size($cerKey) <= 0 || $spaces->size($keyKey) <= 0) {
                Log::warning("CER/KEY vacío para branch {$branch->branch_id}");
                continue;
            }

            // 2) Lee contenidos y normaliza passphrase
            $cerContents = $spaces->get($cerKey);
            $keyContents = $spaces->get($keyKey);
            $pass = trim($branch->passphrase);

            $cookiePath = storage_path("app/sat_cookies/cookies.json");
            if (file_exists($cookiePath)) {
                @unlink($cookiePath); // fuerza sesión nueva
            }
            if (! is_dir(dirname($cookiePath))) {
                mkdir(dirname($cookiePath), 0777, true);
            }

            try {
                $tmpDir = storage_path('app/tmp_fiel');
                if (! is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
                $cerPath = $tmpDir.'/test.cer';
                $keyPath = $tmpDir.'/test.key';
                file_put_contents($cerPath, $cerContents);
                file_put_contents($keyPath, $keyContents);

                // 2) Intenta construir la Credential completa: aquí sabremos si pass/pareja son correctos
                $credential = \PhpCfdi\Credentials\Credential::openFiles($cerPath, $keyPath, $pass);
                $cert = $credential->certificate();
            } catch (\Throwable $e) {
                // Contraseña incorrecta, llave no pertenece al cert, formato inválido, etc.
                Log::error('FIEL inválida: '.$e->getMessage());
                echo "FIEL inválida para branch {$branch->id}: {$e->getMessage()}\n";
                continue;
            }

            $cookieJar = new FileCookieJar(storage_path('app/sat_cookies/cookies.json'), true);
            $client = new Client([
                    'timeout'         => 300,
                    'connect_timeout' => 30,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) SATScraper',
                    ],
                    'curl' => [
                        CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
                        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CAINFO => base_path('certificates/cacert.pem'),
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                    ],
                    'verify' => base_path('certificates/cacert.pem')
                ]);

            $gateway   = new SatHttpGateway($client, $cookieJar);

            $satScraper = new SatScraper(FielSessionManager::create($credential), $gateway);
            
            $from = new DateTimeImmutable($fechaHoraCero, new DateTimeZone('America/Mexico_City'));
            $to   = new DateTimeImmutable($fechaHoraActual, new DateTimeZone('America/Mexico_City'));

            $query = new QueryByFilters($from, $to);
            $query
                ->setDownloadType(ScrapperDownloadType::recibidos())
                ->setStateVoucher(StatesVoucherOption::vigentes());

            $list = $satScraper->listByDateTime($query);

            echo "Encontrados {$list->count()} CFDI(s) para branch {$branch->id} desde {$from->format('Y-m-d H:i:s')} hasta {$to->format('Y-m-d H:i:s')}\n";
            
            $dest = storage_path('app/sat_xml');
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }

            $downloadedUuids = $satScraper
                ->resourceDownloader(ResourceType::xml(), $list, 20)
                ->saveTo($dest, true, 0777);
        
                
            foreach ($downloadedUuids as $uuid) {
                try {
                    $uuidUpper = Str::upper($uuid);
                    $remote = "sat_xml/{$uuidUpper}.xml";
                    $remotePdf = "sat_xml/{$uuidUpper}.pdf";
                    if (Storage::disk('spaces')->exists($remote)) {
                        echo "El archivo {$remote} ya existe en Spaces.\n";
                        continue; // ya existe: no subas duplicado
                    }
                    $local = "{$dest}/{$uuid}.xml";
                    // Sube por stream (eficiente)
                    Storage::disk('spaces')->put($remote, fopen($local, 'r'));

                
                    $xmlPath = "{$dest}/{$uuid}.xml";
                    if (!file_exists($xmlPath)) {
                        Log::warning("XML no encontrado para {$uuid}");
                        continue;
                    }

                    if (Storage::disk('spaces')->exists($remotePdf)) {
                        echo "El archivo {$remotePdf} ya existe en Spaces.\n";
                        continue; // ya existe: no subas duplicado
                    }
                    

                    $xml = file_get_contents($xmlPath);
                    $xml = Cleaner::staticClean($xml);
                    $pdfPath = "{$dest}/{$uuid}.pdf";

                    $comprobante = XmlNodeUtils::nodeFromXmlString($xml);
                    $cfdiData    = (new CfdiDataBuilder())->build($comprobante);
                    (new Converter(new Html2PdfBuilder()))
                        ->createPdfAs($cfdiData, $pdfPath);
                    $converter = new Converter(new Html2PdfBuilder());

                    
                    $converter->createPdfAs($cfdiData, $pdfPath);

                    Storage::disk('spaces')->put($remotePdf, fopen($pdfPath, 'r'));

                    $array = json_decode(JsonConverter::convertToJson($xml), true);

                    $invoice_id         = '';
                    $emisor_name        = data_get($array, 'Emisor.Nombre');
                    $emisor_rfc         = data_get($array, 'Emisor.Rfc');
                    $trandate           = data_get($array, 'Fecha');
                    $total              = data_get($array, 'Total');
                    $status             = '1';
                    $subsidiary         = data_get($array, 'Receptor.Nombre');
                    $efecto_comprobante = data_get($array, 'TipoDeComprobante');
                    $trandate_cer       = data_get($array, 'Complemento.TimbreFiscalDigital.FechaTimbrado');
                    $trandate_cancel    = data_get($array, 'Complemento.TimbreFiscalDigital.FechaCancelacion');

                    echo json_encode([
                        'uuid'               => $uuidUpper,
                        'invoice_id'         => $invoice_id,
                        'emisor_name'        => $emisor_name,
                        'emisor_rfc'         => $emisor_rfc,
                        'trandate'           => $trandate,
                        'total'              => $total,
                        'status'             => $status,
                        'subsidiary'         => $subsidiary,
                        'efecto_comprobante' => $efecto_comprobante,
                        'branch_office_id'   => 1,
                        'rfc_pac'            => null,
                        'trandate_cer'       => $trandate_cer,
                        'trandate_cancel'    => $trandate_cancel,
                        'meta_xml'          => null,
                        'xml_path'          => "{$uuid}.xml",
                        'pdf_path'          => "{$uuid}.pdf",
                        'branch_office_id'   => $branch->id,
                        'invoice_company_id' => $branch->invoice_company_id,
                    ])."\n";

                    $invoice = InvoiceSat::create(
                        [
                            'uuid'               => $uuidUpper,
                            'invoice_id'         => $invoice_id,
                            'emisor_name'        => $emisor_name,
                            'emisor_rfc'         => $emisor_rfc,
                            'trandate'           => $trandate,
                            'total'              => $total,
                            'status'             => $status,
                            'subsidiary'         => $subsidiary,
                            'efecto_comprobante' => $efecto_comprobante,
                            'branch_office_id'   => 1,
                            'rfc_pac'            => null,
                            'trandate_cer'       => $trandate_cer,
                            'trandate_cancel'    => $trandate_cancel,
                            'meta_xml'          => $array,
                            'xml_path'          => "{$uuidUpper}.xml",
                            'pdf_path'          => "{$uuidUpper}.pdf",
                            'branch_office_id'   => $branch->id,
                            'invoice_company_id' => $branch->invoice_company_id,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning("Fallo generando PDF {$uuid}: {$e->getMessage()}");
                    continue;
                }

                
            }
            usleep(300 * 1000); // 300ms entre branches (no es necesario, pero por si acaso)
        }


        
    }

    public function isSatPortalAvailable(): bool
    {
        $urls = [
            'https://cfdiau.sat.gob.mx/',
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true, // Solo headers, no body
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => base_path('certificates/cacert.pem'),
        ]);
        
        foreach ($urls as $url) {
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            
            if ($errno === 0 && in_array($httpCode, [200, 302, 301, 401])) {
                curl_close($ch);
                return true; // Al menos uno responde
            }
        }
        
        curl_close($ch);
        return false; // Todos fallaron
    }

    public function satStatus(){
        return $this->isSatPortalAvailable();
    }
    
}



