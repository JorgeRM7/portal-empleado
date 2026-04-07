<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityLogController
{
    /**
     * Mostrar lista de actividades/acciones
     */
    public function index(Request $request)
    {
        
        
        // ✅ Renderizar con Inertia
        return Inertia::render('ActivityLogs/Index');
    }

    public function getLogs(Request $request) {
        $query = DB::table('logs')
            ->leftJoin('users', 'logs.user_id', '=', 'users.id')
            ->select('logs.*', 'users.email as user_email', 'users.name as user_name');
        
        // 🔍 Filtros
        if ($request->filled('action')) {
            $query->where('logs.action', $request->action);
        }
        
        if ($request->filled('table_name')) {
            $query->where('logs.table_name', $request->table_name);
        }
        
        if ($request->filled('user_id')) {
            $query->where('logs.user_id', $request->user_id);
        }
        
        if ($request->filled('relationship_id')) {
            $query->where('logs.relationship_id', $request->relationship_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('logs.date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('logs.date', '<=', $request->date_to);
        }
        
        
        
        // 📊 Paginación
        $logs = $query->latest('date')->get();

        return $logs;
    }
    
    /**
     * Mostrar detalle de una actividad
     */
    public function show($id)
    {
        $log = DB::table('logs')
            ->leftJoin('users', 'logs.user_id', '=', 'users.id')
            ->select('logs.*', 'users.email as user_email', 'users.name as user_name')
            ->where('logs.id', $id)
            ->first();
        
        if (!$log) {
            return redirect()->back()->with('error', 'Actividad no encontrada');
        }
        
        $oldData = $log->old_data ? json_decode($log->old_data, true) : null;
        
        return Inertia::render('ActivityLogs/Show', [
            'log' => $log,
            'oldData' => $oldData,
        ]);
    }
}