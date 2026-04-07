<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Employee;
use App\Models\PayrollInvoice;
use App\Models\PayrollInvoiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class PayrollInvoiceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id')->where('status', '!=', 'termination')->get();
        $payrollTypes = PayrollInvoiceType::select('id', 'name')->get();
        return Inertia::render('PayrollInvoices/Index', [
            'branchOffices' => $branchOffices,
            'employees' => $employees,
            'payrollTypes' => $payrollTypes
        ]);
    }

    public function getData(Request $request){
        $data = PayrollInvoice::index($request->planta, $request->empleado, $request->semana,  $request->tipo_recibo, $request->anio, $request->correo);
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
         $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $payRollInvoiceType = PayrollInvoiceType::all();
        return Inertia::render('PayrollInvoices/Create', [
            'branchOffices' => $branchOffices,
            'payRollInvoiceType' => $payRollInvoiceType
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:zip,rar|max:10240',
            'branchoffice' => 'required|exists:branch_offices,id',
            'week' => 'required',
            'receipt_type' => 'required'
        ]);

        $branch_office = BranchOffice::findOrFail($request->branchoffice);
        [$year, $week] = explode('-W', $request->week);

        $disk = Storage::disk('remote_sftp');
        $baseDir = 'payroll-inv/' .  $branch_office->code. '/' .date('Y').'/'.$week;
        $disk->makeDirectory($baseDir);

        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'zip') {
                $resultado = $this->extraerConZipArchive($file, $disk, $baseDir, $week, $request->receipt_type, $year, $request->branchoffice);
            } elseif ($extension === 'rar') {
                $resultado = $this->extraerCon7Zip($file, $disk, $baseDir, $week, $request->receipt_type, $year, $request->branchoffice);
            }

            $totalGuardados = count($resultado['guardados']);
            $totalFallidos = count($resultado['fallidos']);

            // Determinar estado de la respuesta
            $success = $totalGuardados > 0;
            $severity = $totalFallidos > 0 ? 'warning' : 'success';

            return response()->json([
                'success' => $success,
                'severity' => $severity,
                'message' => $this->obtenerMensaje($totalGuardados, $totalFallidos),
                'total_guardados' => $totalGuardados,
                'total_failidos' => $totalFallidos,
                'archivos_guardados' => $resultado['guardados'],
                'archivos_failidos' => $resultado['fallidos']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'severity' => 'error',
                'message' => 'Error crítico: ' . $e->getMessage(),
                'archivos_guardados' => [],
                'archivos_failidos' => []
            ], 500);
        }
    }

    private function extraerCon7Zip($file, $disk, $baseDir, $week, $receipt_type, $year, $branchoffice)
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rar_extract_' . uniqid();
        $tempRarPath = $tempDir . DIRECTORY_SEPARATOR . $file->getClientOriginalName();
        
        if (!mkdir($tempDir, 0755, true)) {
            throw new \Exception('No se pudo crear directorio temporal');
        }

        $file->move($tempDir, basename($tempRarPath));

        // === DETECTAR SISTEMA OPERATIVO Y RUTA DEL BINARIO ===
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $sevenZipPath = 'C:\Program Files\7-Zip\7z.exe';
            $extractedDir = $tempDir . '\\extracted';
            $pathSeparator = '\\';
        } else {
            // Linux/Mac
            $sevenZipPath = $this->buscarBinario7z();
            $extractedDir = $tempDir . '/extracted';
            $pathSeparator = '/';
        }
        
        if (!file_exists($sevenZipPath) && !is_executable($sevenZipPath)) {
            throw new \Exception('7-Zip no está instalado. Ruta buscada: ' . $sevenZipPath);
        }

        // === CONSTRUIR COMANDO SEGÚN SO ===
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "\"{$sevenZipPath}\" x -o\"{$tempDir}\\extracted\" -y \"{$tempRarPath}\"";
        } else {
            // Linux: usar escapeshellarg para seguridad
            $command = escapeshellcmd($sevenZipPath) . 
                    ' x -o' . escapeshellarg($extractedDir) . 
                    ' -y ' . escapeshellarg($tempRarPath) . 
                    ' 2>&1'; // Capturar stderr para debug
        }
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorDetail = implode("\n", $output);
            throw new \Exception("Error al extraer el archivo RAR. Código: {$returnCode}\nDetalle: {$errorDetail}");
        }

        $archivosGuardados = [];
        $archivosFallidos = [];
        
        if (is_dir($extractedDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractedDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $archivo) {
                if ($archivo->isFile()) {
                    $nombreArchivo = $archivo->getFilename();
                    $validacion = $this->extraerYValidarIdEmpleado($nombreArchivo);

                    if (!$validacion['valid']) {
                        $archivosFallidos[] = [
                            'nombre' => $nombreArchivo,
                            'error' => $validacion['error']
                        ];
                        continue;
                    }

                    if (preg_match_all('/_(\d+)/', $nombreArchivo, $matches)) {
                        $idEmpleado = end($matches[1]);
                    } else {
                        $idEmpleado = null;
                    }

                    try {
                        $contenido = file_get_contents($archivo->getPathname());
                        $remotePath = $baseDir . '/' . $nombreArchivo;
                        $disk->put($remotePath, $contenido);

                        $archivosGuardados[] = [
                            'nombre' => $nombreArchivo,
                            'ruta' => $remotePath,
                            'tamaño' => filesize($archivo->getPathname()),
                            'id_empleado' => $validacion['id']
                        ];

                        PayrollInvoice::create([
                            'pdf_path' => $remotePath,
                            'week' => $week,
                            'year' => $year,
                            'employee_id' => $idEmpleado,
                            'branch_office_id' => $branchoffice,
                            'payroll_invoices_type_id' => $receipt_type,
                            'send_correo' => 0
                        ]);
                    } catch (\Exception $e) {
                        $archivosFallidos[] = [
                            'nombre' => $nombreArchivo,
                            'error' => "Error al guardar: " . $e->getMessage()
                        ];
                    }
                }
            }
        }

        $this->eliminarDirectorio($tempDir);

        return [
            'guardados' => $archivosGuardados,
            'fallidos' => $archivosFallidos
        ];
    }

    private function extraerConZipArchive($file, $disk, $baseDir, $week, $receipt_type, $year, $branchoffice)
    {
        $tempZipPath = tempnam(sys_get_temp_dir(), 'zip_') . '.zip';
        $file->move(dirname($tempZipPath), basename($tempZipPath));

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) !== true) {
            throw new \Exception('No se pudo abrir el ZIP');
        }

        $archivosGuardados = [];
        $archivosFallidos = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $nombreArchivo = $stat['name'];

            if (substr($nombreArchivo, -1) === '/') continue;

            $validacion = $this->extraerYValidarIdEmpleado($nombreArchivo);

            if (!$validacion['valid']) {
                $archivosFallidos[] = [
                    'nombre' => basename($nombreArchivo),
                    'error' => $validacion['error']
                ];
                continue;
            }

            $idEmpleado = $validacion['id'];
            $nombreLimpio = basename($nombreArchivo);
            $remotePath = $baseDir . '/' . $nombreLimpio;

            if (preg_match_all('/_(\d+)/', $nombreLimpio, $matches)) {
                $idEmpleado = end($matches[1]);
            } else {
                $idEmpleado = null; // O 'sin_id'
            }

            // Obtener contenido
            $contenido = $zip->getFromIndex($i);
            if ($contenido === false) {
                $archivosFallidos[] = [
                    'nombre' => $nombreLimpio,
                    'error' => "No se pudo leer el contenido del archivo"
                ];
                continue;
            }

            // Guardar en SFTP
            try {
                $disk->put($remotePath, $contenido);
                
                $archivosGuardados[] = [
                    'nombre' => $nombreLimpio,
                    'ruta' => $remotePath,
                    'tamaño' => $stat['size'],
                    'id_empleado' => $idEmpleado
                ];

                PayrollInvoice::create([
                    'pdf_path' => $remotePath,
                    'week' => $week,
                    'year' => $year,
                    'employee_id' => $idEmpleado,
                    'branch_office_id' => $branchoffice,
                    'payroll_invoices_type_id' => $receipt_type,
                    'send_correo' => 0
                ]);
            } catch (\Exception $e) {
                $archivosFallidos[] = [
                    'nombre' => $nombreLimpio,
                    'error' => "Error al guardar: " . $e->getMessage()
                ];
            }
        }

        $zip->close();
        unlink($tempZipPath);

        return [
            'guardados' => $archivosGuardados,
            'fallidos' => $archivosFallidos
        ];
    }

    private function extraerYValidarIdEmpleado($nombreArchivo)
    {
        $nombre = basename($nombreArchivo);
        
        if (!preg_match('/_(\d+)(?:\.|$|_)/', $nombre, $matches)) {
            return [
                'valid' => false,
                'id' => null,
                'error' => "El archivo '{$nombre}' no tiene un ID de empleado válido. Formato esperado: nombre_12345.ext"
            ];
        }

        $idEmpleado = $matches[1];

        if (!is_numeric($idEmpleado) || strlen($idEmpleado) < 1 || strlen($idEmpleado) > 10) {
            return [
                'valid' => false,
                'id' => null,
                'error' => "El archivo '{$nombre}' tiene un ID de empleado inválido: '{$idEmpleado}'"
            ];
        }

        if (!$this->empleadoExiste($idEmpleado)) {
            return [
                'valid' => false,
                'id' => null,
                'error' => "El archivo '{$nombre}' tiene un ID de empleado que no existe: '{$idEmpleado}'"
            ];
        }

        return [
            'valid' => true,
            'id' => $idEmpleado,
            'error' => null
        ];
    }

    private function empleadoExiste($idEmpleado)
    {
        return Employee::where('id', $idEmpleado)->exists();
    }

    // Limpieza de directorio temporal
    private function eliminarDirectorio($dir)
    {
        if (!is_dir($dir)) return;
        
        $archivos = array_diff(scandir($dir), ['.', '..']);
        foreach ($archivos as $archivo) {
            $ruta = $dir . DIRECTORY_SEPARATOR . $archivo;
            is_dir($ruta) ? $this->eliminarDirectorio($ruta) : unlink($ruta);
        }
        rmdir($dir);
    }

    private function buscarBinario7z()
    {
        $posiblesRutas = [
            '/usr/bin/7z',
            '/usr/local/bin/7z',
            '/snap/bin/7z',
            '7z',
        ];

        foreach ($posiblesRutas as $ruta) {
            if (file_exists($ruta) && is_executable($ruta)) {
                return $ruta;
            }
        }

        $rutaWhich = trim(shell_exec('which 7z 2>/dev/null'));
        if ($rutaWhich && file_exists($rutaWhich)) {
            return $rutaWhich;
        }

        return '7z';
    }

    private function obtenerMensaje($guardados, $fallidos)
    {
        if ($guardados > 0 && $fallidos === 0) {
            return "✅ {$guardados} archivo(s) guardado(s) correctamente";
        } elseif ($guardados > 0 && $fallidos > 0) {
            return "⚠️ {$guardados} archivo(s) guardado(s), {$fallidos} archivo(s) con errores";
        } else {
            return "❌ Ningún archivo pudo ser guardado. Verifica la nomenclatura de los archivos";
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PayrollInvoice $payrollInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayrollInvoice $payrollInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayrollInvoice $payrollInvoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PayrollInvoice $payrollInvoice)
    {
        //
    }

    public function downloadDocumentHostinger($id)
    {
        $invoice = PayrollInvoice::findOrFail($id);

        if (!$invoice->pdf_path) {
            abort(404, 'Este registro no tiene un documento adjunto.');
        }

        $disk = Storage::disk('remote_sftp');

        if (!$disk->exists($invoice->pdf_path)) {
            abort(404, 'El archivo no se encontró en el servidor remoto.');
        }
        return $disk->response($invoice->pdf_path);
    }

    public function downloadDocumentDigitalOcean($id)
    {
        $invoice = PayrollInvoice::findOrFail($id);

        if (!$invoice->pdf_path) {
            abort(404, 'Este registro no tiene un documento adjunto.');
        }

        $disk = Storage::disk('spaces');

        if (!$disk->exists($invoice->pdf_path)) {
            abort(404, 'El archivo no se encontró en el servidor remoto.');
        }
        return $disk->response($invoice->pdf_path);
    }

    public function queuMail(Request $request)
    {
        $ids = $request->ids;

        PayrollInvoice::whereIn('id', $ids)->update([
            'estatus_correo' => null,
            'send_correo' => 1
        ]);

        return redirect()->back();
    }

    private function getDiskByPathPrefix($path)
    {
        
        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'invoices/')) {
            return Storage::disk('spaces');
        }

        if (str_starts_with($normalizedPath, 'payroll-inv/')) {
            return Storage::disk('remote_sftp');
        }

        return Storage::disk('remote_sftp');
    }

    public function downloadDocuments(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:payroll_invoices,id'
        ]);

        $ids = $request->input('ids');
        $invoices = PayrollInvoice::whereIn('id', $ids)->get();

        if ($invoices->isEmpty()) {
            // ✅ Retornar JSON en lugar de abort
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron registros válidos.',
                'errores' => []
            ], 404);
        }

        $zipFilename = 'nomina_' . date('Y-m-d_H-i-s') . '.zip';
        $tempZipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el archivo ZIP',
                'errores' => []
            ], 500);
        }

        $archivosAgregados = 0;
        $errores = [];
        $archivosExitosos = [];

        foreach ($invoices as $invoice) {
            if (!$invoice->pdf_path) {
                $errores[] = [
                    'id' => $invoice->id,
                    'error' => 'No tiene documento adjunto'
                ];
                continue;
            }

            $disk = $this->getDiskByPathPrefix($invoice->pdf_path);
            
            if (!$disk->exists($invoice->pdf_path)) {
                $errores[] = [
                    'id' => $invoice->id,
                    'error' => 'Archivo no encontrado en el servidor'
                ];
                continue;
            }

            try {
                $contenido = $disk->get($invoice->pdf_path);
                $nombreEnZip = basename($invoice->pdf_path);
                
                if ($zip->locateName($nombreEnZip) !== false) {
                    $pathInfo = pathinfo($nombreEnZip);
                    $nombreEnZip = $pathInfo['filename'] . '_' . $invoice->id . '.' . $pathInfo['extension'];
                }
                
                $zip->addFromString($nombreEnZip, $contenido);
                $archivosAgregados++;
                $archivosExitosos[] = ['id' => $invoice->id, 'nombre' => $nombreEnZip];

            } catch (\Exception $e) {
                $errores[] = [
                    'id' => $invoice->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        $zip->close();

        if ($archivosAgregados === 0) {
            unlink($tempZipPath);
            // ✅ Retornar JSON con detalles de errores en lugar de abort
            return response()->json([
                'success' => false,
                'message' => 'No se pudo descargar ningún archivo',
                'errores' => $errores
            ], 404);
        }

        // Preparar datos de errores para el header
        $erroresData = [
            'total_solicitados' => count($invoices),
            'total_exitosos' => $archivosAgregados,
            'total_errores' => count($errores),
            'errores' => $errores,
            'exitosos' => $archivosExitosos
        ];

        $headers = [
            'X-Download-Status' => !empty($errores) ? 'partial' : 'success',
            'X-Download-Errors-Count' => count($errores),
            'X-Download-Errors' => base64_encode(json_encode($erroresData, JSON_UNESCAPED_UNICODE))
        ];

        if (!empty($errores)) {
            Log::warning('Errores en descarga ZIP:', $erroresData);
        }

        return response()
            ->download($tempZipPath, $zipFilename, $headers)
            ->deleteFileAfterSend(true);
    }

    public function multipleDestroy(Request $request){
        $ids = $request->ids;

        PayrollInvoice::whereIn('id', $ids)->delete();

        return redirect()->back();
    }
}
