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
        $usersCount = User::count();
        $idEmployee = Employee::select('id')->where('user_id', Auth::id())->get();
        return Inertia::render("Dashboard", ['users' => $usersCount, 'idEmployee' => $idEmployee]);
    }

    public function metrics()
    {
        $dashboard_data = Dashboard::metrics();
        return response()->json($dashboard_data);
    }
}
