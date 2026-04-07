<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class LaravelLogController
{
    protected $logPath;
    protected $maxLines = 1000; // Límite de líneas a leer

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    /**
     * Mostrar logs de Laravel
     */
    public function index(Request $request)
    {
        try {
            // Verificar si existe el archivo
            if (!File::exists($this->logPath)) {
                return Inertia::render('LaravelLogs/Index', [
                    'logs' => [],
                    'totalLogs' => 0,
                    'logSize' => 0,
                    'error' => 'El archivo de logs no existe',
                    'filters' => [],
                ]);
            }

            // Obtener tamaño del archivo
            $logSize = File::size($this->logPath);
            
            // Leer el archivo
            $content = File::get($this->logPath);
            
            // Parsear logs
            $logs = $this->parseLogs($content);
            
            // Aplicar filtros
            $filteredLogs = $this->applyFilters($logs, $request);
            
            // Paginación manual
            $page = $request->get('page', 1);
            $perPage = 50;
            $offset = ($page - 1) * $perPage;
            $paginatedLogs = array_slice($filteredLogs, $offset, $perPage);
            
            return Inertia::render('LaravelLogs/Index', [
                'logs' => [
                    'data' => $paginatedLogs,
                    'total' => count($filteredLogs),
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil(count($filteredLogs) / $perPage),
                ],
                'totalLogs' => count($logs),
                'logSize' => $logSize,
                'error' => null,
                'filters' => [
                    'level' => $request->get('level'),
                    'search' => $request->get('search'),
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                ],
            ]);
            
        } catch (Throwable $e) {
            return Inertia::render('LaravelLogs/Index', [
                'logs' => [],
                'totalLogs' => 0,
                'logSize' => 0,
                'error' => 'Error al leer logs: ' . $e->getMessage(),
                'filters' => [],
            ]);
        }
    }

    /**
     * Mostrar detalle de un log
     */
    public function show($index)
    {
        try {
            if (!File::exists($this->logPath)) {
                return redirect()->back()->with('error', 'El archivo de logs no existe');
            }

            $content = File::get($this->logPath);
            $logs = $this->parseLogs($content);
            
            if (!isset($logs[$index])) {
                return redirect()->back()->with('error', 'Log no encontrado');
            }

            return Inertia::render('LaravelLogs/Show', [
                'log' => $logs[$index],
                'index' => $index,
                'totalLogs' => count($logs),
            ]);
            
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Limpiar logs
     */
    public function clear()
    {
        try {
            if (File::exists($this->logPath)) {
                File::put($this->logPath, '');
            }
            
            return redirect()->back()->with('success', '✅ Logs limpiados correctamente');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Error al limpiar logs: ' . $e->getMessage());
        }
    }

    /**
     * Descargar logs
     */
    public function download()
    {
        if (!File::exists($this->logPath)) {
            abort(404, 'El archivo de logs no existe');
        }

        try {
            $filename = 'laravel-' . date('Y-m-d-His') . '.log';
            $filesize = File::size($this->logPath);
            
            return response()->stream(function() {
                readfile($this->logPath);
                flush();
            }, 200, [
                'Content-Type' => 'text/plain',
                'Content-Length' => $filesize,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Inertia' => 'false',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al descargar logs: ' . $e->getMessage());
            abort(500, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Parsear el archivo de logs
     */
    private function parseLogs($content)
    {
        $logs = [];
        $lines = explode("\n", $content);
        $currentLog = null;
        $currentMessage = [];
        $currentContext = [];
        $inContext = false;
        $inStacktrace = false;
        $stacktrace = [];

        foreach ($lines as $line) {
            // Detectar inicio de nuevo log: [2024-01-01 12:00:00] local.ERROR: Mensaje
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s*(.*)$/', $line, $matches)) {
                // Guardar log anterior si existe
                if ($currentLog !== null) {
                    $currentLog['message'] = trim(implode("\n", $currentMessage));
                    $currentLog['context'] = $currentContext;
                    $currentLog['stacktrace'] = implode("\n", $stacktrace);
                    $logs[] = $currentLog;
                }

                // Iniciar nuevo log
                $currentLog = [
                    'datetime' => $matches[1],
                    'date' => explode(' ', $matches[1])[0],
                    'time' => explode(' ', $matches[1])[1],
                    'environment' => $matches[2],
                    'level' => $matches[3],
                    'raw_message' => $matches[4],
                ];
                
                $currentMessage = [$matches[4]];
                $currentContext = [];
                $stacktrace = [];
                $inContext = false;
                $inStacktrace = false;
                
            } elseif ($currentLog !== null) {
                // Continuar mensaje actual
                if (trim($line) === '' && empty($currentMessage)) {
                    continue;
                }

                // Detectar contexto JSON
                if (preg_match('/^\{.*\}$/', trim($line))) {
                    try {
                        $currentContext = json_decode(trim($line), true);
                        $inContext = true;
                    } catch (\Exception $e) {
                        $currentMessage[] = $line;
                    }
                } elseif ($inContext) {
                    // Continuar contexto
                    $currentMessage[] = $line;
                } elseif (str_starts_with(trim($line), '#')) {
                    // Stack trace
                    $inStacktrace = true;
                    $stacktrace[] = $line;
                } elseif ($inStacktrace) {
                    $stacktrace[] = $line;
                } else {
                    $currentMessage[] = $line;
                }
            }
        }

        // Agregar último log
        if ($currentLog !== null) {
            $currentLog['message'] = trim(implode("\n", $currentMessage));
            $currentLog['context'] = $currentContext;
            $currentLog['stacktrace'] = implode("\n", $stacktrace);
            $logs[] = $currentLog;
        }

        // Ordenar por fecha (más reciente primero)
        usort($logs, function($a, $b) {
            return strcmp($b['datetime'], $a['datetime']);
        });

        return array_values($logs);
    }

    /**
     * Aplicar filtros a los logs
     */
    private function applyFilters($logs, Request $request)
    {
        $filtered = $logs;

        // Filtrar por nivel
        if ($request->filled('level')) {
            $level = strtoupper($request->level);
            $filtered = array_filter($filtered, function($log) use ($level) {
                return strtoupper($log['level']) === $level;
            });
        }

        // Filtrar por búsqueda
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $filtered = array_filter($filtered, function($log) use ($search) {
                return str_contains(strtolower($log['message']), $search) ||
                       str_contains(strtolower($log['raw_message']), $search) ||
                       str_contains(strtolower(json_encode($log['context'])), $search);
            });
        }

        // Filtrar por fecha desde
        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            $filtered = array_filter($filtered, function($log) use ($dateFrom) {
                return $log['date'] >= $dateFrom;
            });
        }

        // Filtrar por fecha hasta
        if ($request->filled('date_to')) {
            $dateTo = $request->date_to;
            $filtered = array_filter($filtered, function($log) use ($dateTo) {
                return $log['date'] <= $dateTo;
            });
        }

        return array_values($filtered);
    }
}