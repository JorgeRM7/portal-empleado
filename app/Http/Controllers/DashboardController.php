<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController
{
    public function index()
    {
        return Inertia::render("Dashboard", []);
    }

    public function show($id)
    {
        $data = Dashboard::getData($id);
        return response()->json($data);
    }

    public function vacacionesDetalle($id)
    {
        $data = Dashboard::dashboardVacaciones([
            'empleados' => [$id]
        ]);

        return response()->json($data);
    }

    public function incidenciasDetalle($id)
    {
        $data = Dashboard::dashboardIncidencias([
            'empleados' => [$id]
        ]);

        return response()->json($data);
    }
}
