<?php


use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeeklyAssistencesController;


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
    Route::get('/dashboard/show/{id}', [DashboardController::class, 'show'])->name('dashboard.show');
    
    Route::get('weekly-assistences/filter-data', [WeeklyAssistencesController::class, 'filter_data'])
        ->name('weekly-assistences.filter-data');
    Route::resource('weekly-assistences', WeeklyAssistencesController::class)->names([
        'index' => 'weekly-assistences'
    ]);

    // ASISTENCIAS
    Route::prefix('assistences')->group(function () {

        // ASISTENCIAS DIARIAS
        // Route::get('assistences-daily/filter-data', [AssistencesDailysController::class, 'filter_data'])
        // ->name('assistences-daily.filter-data');

        
    });


});



