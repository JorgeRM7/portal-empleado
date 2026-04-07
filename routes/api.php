<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiPruebaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiferencesResumeController;
use App\Http\Controllers\EmployeeCompensationController;
use App\Http\Controllers\EmployeeIncidencesController;
use App\Http\Controllers\EmployeeOvertimeController;
use App\Http\Controllers\EmployeeOvertimeEstimateController;
use App\Http\Controllers\EmployeeSearchController;
use App\Http\Controllers\EmployeeShiftRoleController;
use App\Http\Controllers\EmployeeVacationController;
use App\Http\Controllers\InvoiceSatController;
use App\Http\Controllers\LaravelLogController;
use App\Http\Controllers\PayrollInvoiceController;
use App\Http\Controllers\ShiftRoleCycleController;
use App\Http\Controllers\StateHistoryController;
use App\Http\Controllers\TxTController;
use App\Models\User;
use App\Models\BranchOffice;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\EmployeeSearch;
use App\Models\PayrollInvoice;
use App\Models\Views;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/prueba', [ApiPruebaController::class, 'index']);

Route::get('/users', function (Request $request) {

    $perPage = $request->get('per_page', 50);

    $filters = json_decode($request->get('filters', '{}'), true);

    $query = User::select('id', 'name', 'username', 'email');

    if (!empty($filters['global']['value'])) {
        $search = $filters['global']['value'];

        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    foreach (['id', 'name', 'username', 'email'] as $field) {
        if (!empty($filters[$field]['value'])) {
            $value = $filters[$field]['value'];

            $query->where($field, 'like', "%{$value}%");
        }
    }

    $users = $query->paginate($perPage);

    return response()->json($users);
});

Route::get("/employee-branchOffices", function (Request $request) {
    $employees = Employee::select('id', 'full_name')->where('branch_office_id', $request->branchOfficeId)->get();
    return response()->json($employees);
});

Route::get("/permissions-views", function (Request $request) {
    $views = Views::all()->map(function ($view) {
        $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
        $last = basename(trim($path, '/'));
        $base = Str::slug($last, '-');

        $permissions = Permission::where('name', 'like', "{$base}.%")
            ->orderBy('name')
            ->get();

        return [
            'id'          => $view->id,
            'name'        => $view->name,
            'url'         => $view->url,
            'base'        => $base,
            'permissions' => $permissions,
        ];
    });

    return response()->json($views);

});

Route::get('/permissions-views-users', function (Request $request) {
    $user = User::find($request->id);
    
    // Cargar overrides
    $userOverrides = $user?->permissionOverrides()
        ->with('permission')
        ->get()
        ->mapWithKeys(function ($override) {
            $key = $override->view_name 
                ? "{$override->view_name}.{$override->permission->name}"
                : $override->permission->name;
            return [$key => $override->is_allowed];
        })
        ->toArray();

    $views = Views::all()->map(function ($view) use ($user, $userOverrides) {
        $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
        $last = basename(trim($path, '/'));
        $base = Str::slug($last, '-');

        $permissions = Permission::where('name', 'like', "{$base}.%")
            ->orderBy('name')
            ->get()
            ->map(function ($perm) use ($user, $userOverrides, $view) {
                // ✨ 1. Primero verificar si el permiso existe en DB
                $permissionExists = Permission::where('name', $perm->name)
                    ->where('guard_name', 'web')
                    ->exists();

                // ✨ 2. Solo consultar al usuario si el permiso existe
                $baseAssigned = false;
                if ($user) {
                    try {
                        $baseAssigned = $user->hasPermissionTo($perm->name);
                    } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                        // El permiso no existe en Spatie, asumimos false
                        $baseAssigned = false;
                        Log::warning("Permiso no existe: {$perm->name}", [
                            'user_id' => $user->id,
                            'view' => $view->name
                        ]);
                    }
                }
                
                // 3. Lógica de overrides
                $overrideKey = "{$view->name}.{$perm->name}";
                $overrideKeyAlt = $perm->name;
                
                $hasOverride = isset($userOverrides[$overrideKey]) || isset($userOverrides[$overrideKeyAlt]);
                $overrideValue = $userOverrides[$overrideKey] ?? $userOverrides[$overrideKeyAlt] ?? null;
                
                $finalAssigned = $hasOverride ? $overrideValue : $baseAssigned;

                return [
                    'name'           => $perm->name,
                    'id'             => $perm->id,
                    'assigned'       => $finalAssigned,
                    'base_assigned'  => $baseAssigned,
                    'is_overridden'  => $hasOverride,
                    'override_value' => $overrideValue,
                    'exists'         => $permissionExists, // ✨ Útil para debug
                ];
            });

        return [
            'id'          => $view->id,
            'name'        => $view->name,
            'url'         => $view->url,
            'base'        => $base,
            'permissions' => $permissions,
        ];
    });

    return response()->json($views);
});

Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);

Route::get('/compensations/filter-data', [EmployeeCompensationController::class,'filterData'])
            ->name('compensations.filter-data');

Route::get('vacations', [EmployeeVacationController::class, 'vacaciones']);
Route::get('vacations/balances', [EmployeeVacationController::class, 'balances']);
Route::get('vacations/movements', [EmployeeVacationController::class, 'movements']);

Route::get('incidences/employee', [EmployeeIncidencesController::class,'getIncidencesByEmployeeId']);
Route::get('incidences/getIncidences', [EmployeeIncidencesController::class, 'getIncidences']);
Route::get('incidences/getIncidencesDataLoad', [EmployeeIncidencesController::class, 'getIncidencesDataLoad']);

Route::get('txts', [TxTController::class, 'filterData']);
Route::get('txts/search-employee-data', [TxTController::class, 'searchEmployeeData']);

Route::get('shift-role-cycles', [ShiftRoleCycleController::class, 'getShiftRoleCycles']);

Route::get('employee-shift-roles', [EmployeeShiftRoleController::class, 'getEmployeeShiftRoles']);

Route::get('getTxtHistory', [TxTController::class, 'getTxtHistory']);

Route::get('employee-overtime-estimates', [EmployeeOvertimeEstimateController::class, 'index']);

Route::get('diferences-resume', [DiferencesResumeController::class, 'index']);

Route::get('employee-overtime', [EmployeeOvertimeController::class, 'getData']);

Route::get('employees-overtimes', [EmployeeOvertimeController::class, 'index']);

Route::get('employees-search', [EmployeeSearchController::class, 'search']);

Route::get('payroll-invoice', [PayrollInvoiceController::class, 'getData']);

Route::get('state-history', [StateHistoryController::class, 'getData']);

Route::get('/laravel-logs/download', [LaravelLogController::class, 'download']);

Route::get('getTxtHistoryExcel', [TxTController::class, 'getTxtHistoryExcel']);

Route::get('activity-logs', [ActivityLogController::class, 'getLogs']);

Route::get('/sat-download', [InvoiceSatController::class, 'descargaSat']);

Route::get('/sat', [InvoiceSatController::class, 'satStatus']);

Route::get('/sat-test', [InvoiceSatController::class, 'descargaSatTest']);
