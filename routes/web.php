<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeeklyAssistencesController;
use App\Http\Controllers\ComplaintsModuleController;

use App\Http\Controllers\EmployeeIncidencesController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PayrollInvoiceController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TermConditionController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\LibraryController;

use App\Http\Controllers\NotificationController;

use App\Http\Controllers\DeviceTokensController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Storage;


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

    Route::put('/user/email', [UserController::class, 'updateEmail'])
        ->name('user-email.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/show/{id}', [DashboardController::class, 'show'])->name('dashboard.show');
    Route::get('/dashboard/vacaciones/{id}', [DashboardController::class, 'vacacionesDetalle'])
    ->name('dashboard.vacaciones');
    Route::get('/dashboard/incidencias/{id}', [DashboardController::class, 'incidenciasDetalle'])
    ->name('dashboard.incidencias');

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
    Route::get('get-attendance', [EmployeeIncidencesController::class, 'getAssistanceData']);

    Route::prefix('payroll')->group(function () {

        Route::resource('payroll-invoices', PayrollInvoiceController::class)
            ->names([
                'index' => '/payroll-invoices',
            ]);
        Route::post('payroll-invoices/send-mail', [PayrollInvoiceController::class, 'sendInvoiceEmail'])
            ->name('payroll-invoices.send-mail');

        Route::get('payroll-invoices/invoice-h/{id}', [PayrollInvoiceController::class, 'downloadDocumentHostinger']);
        Route::get('payroll-invoices/invoice-d/{id}', [PayrollInvoiceController::class, 'downloadDocumentDigitalOcean']);
        Route::post('payroll-invoices/download', [PayrollInvoiceController::class, 'downloadDocuments']);
    });

    Route::get('payroll-invoice', [PayrollInvoiceController::class, 'getData']);


    // Route::prefix('complaints')->group(function () {

    //     Route::resource('/', ComplaintsModuleController::class)->names([
    //         'index' => 'complaints'
    //     ]);
    // });

    Route::get('complaints/filter-data', [ComplaintsModuleController::class, 'filter_data'])
        ->name('complaints.filter-data');

    Route::post('complaints/improve-writing', [ComplaintsModuleController::class, 'improveWriting'])
    ->name('complaints.improve');

    Route::post('complaints/rate-response', [ComplaintsModuleController::class, 'rateResponse'])
    ->name('complaints.rate');

    Route::resource('complaints', ComplaintsModuleController::class)->names([
        'index' => '/complaints'
    ]);

    Route::get('/complaints/{complaint_id}/files/{filename}', [ComplaintsModuleController::class, 'downloadFile'])
    ->name('complaints.files.download');

    Route::resource('term-conditions', TermConditionController::class)->names([
        'index' => 'term-conditions'
    ]);

    Route::resource('device-tokens', DeviceTokensController::class)->names([
        'index' => 'device-tokens'
    ]);

    Route::get('/posts', [PostController::class, 'index'])
        ->name('posts.index');
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])
        ->name('posts.like');
    Route::get('/storage/img/social/{path}', [PostController::class, 'showImg'])
        ->where('path', '.*')
        ->name('posts.image');
    Route::get('/posts/{post}/show', [PostController::class, 'show'])
        ->name('posts.show');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/ai-assistant', [ChatController::class, 'processVoiceCommand']);

    Route::resource('library', LibraryController::class)->names([
        'index' => '/library'
    ]);

    //Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications-page', [NotificationController::class, 'notificationsPage']);
    Route::post('/notifications-page', [NotificationController::class, 'notificationsPage']);
    Route::post('/notifications/update-status', [NotificationController::class, 'update'])
    ->name('notifications.updateStatus');

    Route::get('/employees/{id}/photo', [UserController::class, 'getPhoto'])->name('employees.photo');

    Route::put('/password', [PasswordController::class, 'update'])
        ->name('password.update-user-employee');

});

Route::get('/ver-archivo/{path}', function ($path) {
    $disk = Storage::disk('remote_sftp');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $file = $disk->get($path);

    return response($file, 200)
        ->header('Content-Type', 'application/pdf');
})->where('path', '.*');

