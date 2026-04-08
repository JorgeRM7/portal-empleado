<?php


use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeIncidencesController;
use App\Http\Controllers\PayrollInvoiceController;

// -----------------------------------------------------
// ROOT / LOGIN
// -----------------------------------------------------

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});




Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
 

    // ASISTENCIAS
    Route::prefix('assistences')->group(function () {

        // ASISTENCIAS DIARIAS
        // Route::get('assistences-daily/filter-data', [AssistencesDailysController::class, 'filter_data'])
        // ->name('assistences-daily.filter-data');

        // Route::resource('assistences-daily', AssistencesDailysController::class)->names([
        //     'index' => 'assistences-daily'
        // ]);
    });

    Route::resource('incidences-employee', EmployeeIncidencesController::class)
        ->names([
            'index' => '/incidences-employee',
        ]);

    Route::get('incidences/getIncidencesDataLoad', [EmployeeIncidencesController::class, 'getIncidencesDataLoad']);
    Route::get('incidences/employee', [EmployeeIncidencesController::class,'getIncidencesByEmployeeId']);
    Route::get('incidences/getIncidences', [EmployeeIncidencesController::class, 'getIncidences']);
    Route::get('incidences/{id_incidence}/pdf', [EmployeeIncidencesController::class, 'createReport'])
            ->name('incidences.pdf');
    Route::get('incidences/{id_incidence}/txt', [EmployeeIncidencesController::class, 'createReport'])
            ->name('incidences.txt');
    
    Route::prefix('payroll')->group(function () {
    
        Route::resource('payroll-invoices', PayrollInvoiceController::class)
            ->names([
                'index' => '/payroll-invoices',
            ]);
        Route::post('payroll-invoices/send-mail', [PayrollInvoiceController::class, 'sendInvoiceEmail'])
            ->name('payroll-invoices.send-mail');
    });

    Route::get('payroll-invoice', [PayrollInvoiceController::class, 'getData']);


});



