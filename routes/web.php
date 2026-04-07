<?php

use App\Http\Controllers\ApiPruebaController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\LogsErrors;

// Controllers
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BenefitsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeePolicyController;
use App\Http\Controllers\DirectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeVacationController;
use App\Http\Controllers\ReasonsController;
use App\Http\Controllers\MovementTypesController;
use App\Http\Controllers\StatusReasonsController;
use App\Http\Controllers\EarningsDeductionsController;
use App\Http\Controllers\ShiftRolesController;
use App\Http\Controllers\NotificationController;
use App\Notifications\RegistroEditado;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BranchOfficeUserController;
use App\Http\Controllers\CategoryIncidencesController;
use App\Http\Controllers\ViewsController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\WeeklyAssistencesController;
use App\Http\Controllers\BranchOfficesController;
use App\Http\Controllers\AbsenteeismController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssistencesController;
use App\Http\Controllers\AssistenceOverTimeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeCompensationController;
use App\Http\Controllers\EmployeeIncidencesController;
use App\Http\Controllers\PayrollTypesController;
use App\Http\Controllers\EmployeeNoRehirableController;
use App\Http\Controllers\PositionsController;
use App\Http\Controllers\VacationsPerEmployeeController;
use App\Http\Controllers\EmployeeBlockIncidentsController;
use App\Http\Controllers\EmployeeCatalogController;
use App\Http\Controllers\EmployeeEfficiencyController;
use App\Http\Controllers\EmployeeShiftRoleController;
use App\Http\Controllers\IncidencesController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AssistencesDailysController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DiferencesResumeController;
use App\Http\Controllers\EmployeeOvertimeController;
use App\Http\Controllers\EmployeeOvertimeEstimateController;
use App\Http\Controllers\EmployeeSalaryAdjustmentsController;
use App\Http\Controllers\EmployeeSearchController;
use App\Http\Controllers\PayrollAccountController;
use App\Http\Controllers\PayrollInvoiceTypesController;
use App\Http\Controllers\PayrollDepartamentsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Overtimes;
use App\Http\Controllers\PayrollInvoiceController;
use App\Http\Controllers\ShiftRoleCycleController;
use App\Http\Controllers\TxTController;
use App\Http\Controllers\PayrollEarningsDeductionsController;
use App\Http\Controllers\EmailsController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\StateHistoryController;
use App\Http\Controllers\H2HDocumentsController;
use App\Http\Controllers\ClassificationsController;
use App\Http\Controllers\LaravelLogController;
use App\Http\Controllers\EmployeeClassController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\HikcentralController;
use App\Http\Controllers\InvoiceSatController;
use App\Http\Controllers\XmlController;
use Illuminate\Http\Request;

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


Route::get('diferences-resume/show', [DiferencesResumeController::class, 'show'])->name('diferences-resume.show');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // ----------------- Dashboard -----------------
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/test', function () {
        return Inertia::render('Prueba/Index');
    });

    Route::get('/prueba', function () {
        return Inertia::render('Prueba/Index');
    })->name('prueba');


    // ASISTENCIAS
    Route::prefix('assistences')->group(function () {

        // ASISTENCIA SEMANAL
        Route::post('weekly-assistences/downloadAll', [WeeklyAssistencesController::class, 'downloadAll'])
        ->name('weekly-assistences.downloadAll');

        Route::get('weekly-assistences/filter-data', [WeeklyAssistencesController::class, 'filter_data'])
        ->name('weekly-assistences.filter-data');

        Route::post('/weekly-assistances/check-turn', [WeeklyAssistencesController::class, 'weeklyAssistenceCheckTurn'])
        ->name('weekly-assistances.check-turn');

        Route::resource('weekly-assistences', WeeklyAssistencesController::class)->names([
            'index' => 'weekly-assistences'
        ]);

        // REGISTRO DE ASISTENCIAS
        Route::get('assistences/filter-data', [AssistencesController::class, 'filter_data'])
        ->name('assistences.filter-data');

        Route::resource('assistences', AssistencesController::class)->names([
            'index' => 'assistences'
        ]);

        // REPORTE DE AUSENTISMOS
        Route::get('absenteeism/filter-data', [AbsenteeismController::class, 'filter_data'])
        ->name('assistences.filter-data');

        Route::resource('absenteeism', AbsenteeismController::class)->names([
            'index' => 'absenteeism'
        ]);

        // REGISTROS H.E
        Route::get('overtime/filter-data', [AssistenceOverTimeController::class, 'filter_data'])
        ->name('overtime.filter-data');

        Route::resource('overtime', AssistenceOverTimeController::class)->names([
            'index' => 'overtimes'
        ]);

        // EVENTOS
        Route::get('events/filter-data', [EventController::class, 'filter_data'])
        ->name('events.filter-data');

        Route::resource('events', EventController::class)->names([
            'index' => 'events'
        ]);

        // ASISTENCIAS DIARIAS
        Route::get('assistences-daily/filter-data', [AssistencesDailysController::class, 'filter_data'])
        ->name('assistences-daily.filter-data');

        Route::resource('assistences-daily', AssistencesDailysController::class)->names([
            'index' => 'assistences-daily'
        ]);
    });

    // EMPLEADOS
    Route::prefix('employee')->group(function () {

        // VACACIONES
        Route::get('vacations/filter-data', [EmployeeVacationController::class, 'filter_data'])
        ->name('vacations.filter-data');

        Route::get('vacations/filter-saldos',[EmployeeVacationController::class, 'filter_data_saldos'])
        ->name('vacations.filter-saldos');

        Route::get('vacations/filter-movimientos',[EmployeeVacationController::class, 'filter_data_movimientos'])
        ->name('vacations.filter-movimientos');

        Route::resource('vacations', EmployeeVacationController::class)->names([
            'index' => 'vacations'
        ]);


        // COMPENSACIONES
        Route::resource('compensations', EmployeeCompensationController::class)
            ->names([
                'index' => '/compensations',
            ]);

        Route::post('compensations/import', [EmployeeCompensationController::class, 'import'])
            ->name('compensations.import');

        Route::post('compensations/multiple-delete', [EmployeeCompensationController::class, 'destroyMultiple'])
            ->name('compensations.multiple-delete');

        Route::post('compensations/approve', [EmployeeCompensationController::class, 'approve'])
            ->name('compensations.approve');

        Route::post('compensations/reject', [EmployeeCompensationController::class, 'reject'])
            ->name('compensations.reject');

        Route::post('compensations/multiple-approve', [EmployeeCompensationController::class,'approveAll'])
            ->name('compensations.multiple-approve');

        Route::post('compensations/multiple-reject', [EmployeeCompensationController::class,'rejectAll'])
            ->name('compensations.multiple-reject');

        // =====================================================
        //  INCIDENCIAS
        // =====================================================
        // Route::resource('incidences', EmployeeIncidencesController::class)
        //     ->names([
        //         'index' => '/incidences',
        //     ]);

        Route::get('incidences/{id_incidence}/pdf', [EmployeeIncidencesController::class, 'createReport'])
            ->name('incidences.pdf');
        Route::get('incidences/{id_incidence}/txt', [EmployeeIncidencesController::class, 'createReport'])
            ->name('incidences.txt');
        Route::put('incidences/approve/{incidence}', [EmployeeIncidencesController::class, 'approve'])
            ->name('incidences.approve');
        Route::put('incidences/reject/{incidence}', [EmployeeIncidencesController::class, 'reject'])
            ->name('incidences.reject');
        Route::post('incidences/multiple-reject', [EmployeeIncidencesController::class,'rejectAll'])
            ->name('incidences.multiple-reject');
        Route::post('incidences/multiple-approve', [EmployeeIncidencesController::class,'approveAll'])
            ->name('incidences.multiple-approve');
        Route::post('incidences/multiple-delete', [EmployeeIncidencesController::class,'deleteAll'])
            ->name('incidences.multiple-delete');
        Route::get('/incidences/{id}/document', [EmployeeIncidencesController::class, 'downloadDocument'])->name('incidences.document');


        // =====================================================
        //  NO RECONTRATABLES
        // =====================================================
        Route::get('no-rehirable/data', [EmployeeNoRehirableController::class, 'data'])
            ->name('no-rehirable.data');

        // Export CSV
        Route::get('no-rehirable/export', [EmployeeNoRehirableController::class, 'exportNoRehirable'])
            ->name('no-rehirable.export');

        Route::resource('no-rehirable', EmployeeNoRehirableController::class)->names([
            'index' => 'no-rehirable'
        ]);

        // =====================================================
        //  INCIDENCIAS
        // =====================================================
        Route::resource('incidences-employee', EmployeeIncidencesController::class)
        ->names([
            'index' => '/incidences-employee',
        ]);

        // =====================================================
        //  VACACIONES POR EMPLEADO
        // =====================================================
        Route::get('vacations-per-employee/filter-vacationsperEmployee',[VacationsPerEmployeeController::class, 'filter_data_vacationsperEmployee'])
            ->name('vacations-per-employee.filter-vacationsperEmployee');

        Route::resource('vacations-per-employee', VacationsPerEmployeeController::class)->names([
            'index' => 'vacations-per-employee'
        ]);

        // =====================================================
        //  BLOQUEAR INCIDENCIAS
        // =====================================================
        Route::get('block-incidents/filter-blockIncidents',[EmployeeBlockIncidentsController::class, 'filter_data_blockIncidents'])
            ->name('block-incidents.filter-blockIncidents');
        Route::resource('block-incidents', EmployeeBlockIncidentsController::class)->names([
            'index' => 'block-incidents'
        ]);

        // =====================================================
        //  CATALOGO DE EMPLEADOS
        // =====================================================
        Route::get('catalog/filter',[EmployeeCatalogController::class, 'filter_data_catalog'])
            ->name('catalog.filter');

        Route::get('catalog/massive-edition', [EmployeeCatalogController::class, 'massive_edition'])
            ->name('catalog.massive-edition');

        Route::post('/catalog/import', [EmployeeCatalogController::class, 'import'])
            ->name('catalog.import');

        Route::post('catalog/termination', [EmployeeCatalogController::class, 'termination'])
            ->name('catalog.termination');

        Route::post('catalog/download', [EmployeeCatalogController::class, 'download'])
            ->name('catalog.download');

        Route::post('catalog/reentry', [EmployeeCatalogController::class, 'reentry'])
            ->name('catalog.reentry');

        Route::post('catalog/import', [EmployeeCatalogController::class, 'import'])
            ->name('catalog.import');

        Route::post('catalog/transfer', [EmployeeCatalogController::class, 'transfer'])
            ->name('catalog.transfer');

        Route::post('catalog/downloadAll', [EmployeeCatalogController::class, 'downloadAll'])
            ->name('catalog.downloadAll');

        Route::get('catalog/locations-search', [EmployeeCatalogController::class, 'search'])
            ->name('catalog.locations-search');

        Route::get('catalog/employee-search', [EmployeeCatalogController::class, 'searchEmploye'])
            ->name('catalog.employee-search');

        Route::post('catalog/actualizacion-masiva', [EmployeeCatalogController::class, 'massiveUpdate'])
            ->name('catalog.actualizacionMasiva');

        Route::resource('catalog', EmployeeCatalogController::class)->names([
            'index' => 'catalog'
        ]);

        // =====================================================
        //  TXT
        // =====================================================
        Route::resource('txt', TxTController::class)->names([
            'index' => 'txt'
        ]);

        Route::post('txt/validate', [TxTController::class, 'validateTxT'])
            ->name('txt.validate');

        Route::post('txt/approve', [TxTController::class, 'approveTXT'])
            ->name('txt.approve');
        Route::post('txt/decline', [TxTController::class, 'declineTXT'])
            ->name('txt.decline');
        Route::post('txt/upload', [TxTController::class, 'uploadDocument'])
            ->name('txt.upload');
        Route::get('txt/{id}/download', [TxTController::class, 'downloadDocument'])
            ->name('txt.download');
        Route::get('txt-history', [TxTController::class, 'indexHistory'])
            ->name('txt-history.index');
        Route::get('txt-history/{id}/edit', [TxTController::class, 'editHistory'])
            ->name('txt-history.edit');
        Route::put('txt-history/{id}/update', [TxTController::class, 'updateHistory'])
            ->name('txt-history.update');
        Route::get('txt-history/create', [TxTController::class, 'createHistory'])
            ->name('txt-history.create');
        Route::post('txt-history/store', [TxTController::class, 'storeHistory'])
            ->name('txt-history.store');
        Route::delete('txt-history/{id}/delete', [TxTController::class, 'deleteHistory'])
            ->name('txt-history.delete');

        // =====================================================
        //  EFICIENCIA V2
        // =====================================================
        Route::get('efficiency/filter',[EmployeeEfficiencyController::class, 'filter_data_efficiency'])
            ->name('efficiency.filter');

        Route::post('efficiency/sodexo-validos',[EmployeeEfficiencyController::class, 'sodexo_validos'])
            ->name('efficiency.sodexo');

        Route::post('efficiency/sodexo-import', [EmployeeEfficiencyController::class, 'import_sodexo'])
            ->name('efficiency.sodexo.import');

        Route::resource('efficiency', EmployeeEfficiencyController::class)->names([
            'index' => 'efficiency'
        ]);

        // =====================================================
        //  CICLO DE TURNO POR EMPLEADO
        // =====================================================
        Route::resource('shift-role-cycles', ShiftRoleCycleController::class)->names([
            'index' => 'shift-role-cycles'
        ]);
        Route::post(
            'shift-role-cycles/delete-multiple',
            [ShiftRoleCycleController::class, 'destroyMultiple']
        )->name('shift-role-cycles.destroy-multiple');

        // =====================================================
        //  ROL DE TURNO POR EMPLEADO
        // =====================================================
        Route::resource('employee-shift-roles', EmployeeShiftRoleController::class)->names([
            'index' => 'employee-shift-roles'
        ]);
        Route::post('employee-shift-roles/delete-multiple', [EmployeeShiftRoleController::class, 'destroyMultiple'])->name('employee-shift-roles.destroy-multiple');

        // =====================================================
        //  TIEMPOS EXTRA: ESTIMACIONES Y PAGOS
        // =====================================================
        Route::get('employee-overtimes', [Overtimes::class, 'index'])->name('employee-overtimes.index');
        Route::resource('employee-overtimes/estimates', EmployeeOvertimeEstimateController::class)->names([
            'index' => 'employee-overtimes-estimates'
        ]);
        Route::post('employee-overtime-estimates/generate-pdf', [EmployeeOvertimeEstimateController::class, 'generatePdf'])->name('employee-overtime-estimates.generate-pdf');
        Route::post('employee-overtime-estimates/approve', [EmployeeOvertimeEstimateController::class, 'approve'])->name('employee-overtime-estimates.approve');
        Route::post('employee-overtime-estimates/decline', [EmployeeOvertimeEstimateController::class, 'decline'])->name('employee-overtime-estimates.decline');
        Route::post('employee-overtime-estimates/multi-approve', [EmployeeOvertimeEstimateController::class, 'multiApprove'])->name('employee-overtime-estimates.multi-approve');
        Route::post('employee-overtime-estimates/multi-decline', [EmployeeOvertimeEstimateController::class, 'multiDecline'])->name('employee-overtime-estimates.multi-decline');
        Route::post('employee-overtime-estimates/multi-delete', [EmployeeOvertimeEstimateController::class, 'multiDelete'])->name('employee-overtime-estimates.multi-delete');

        Route::get('diferences-resume/show', [DiferencesResumeController::class, 'show'])->name('diferences-resume.show');
        Route::post('diferences-resume/generate-pdf', [DiferencesResumeController::class, 'generatePdf'])->name('diferences-resume.generate-pdf');

        Route::resource('employee-overtimes/overtimes', EmployeeOvertimeController::class)->names([
            'index' => 'employee-overtimes-overtimes'
        ]);
        Route::post('employee-overtimes/overtimes/approve', [EmployeeOvertimeController::class, 'approve'])->name('overtimes.approve');
        Route::post('employee-overtimes/overtimes/decline', [EmployeeOvertimeController::class, 'decline'])->name('overtimes.decline');
        Route::post('employee-overtimes/overtimes/multi-approve', [EmployeeOvertimeController::class, 'multiApprove'])->name('overtimes.multi-approve');
        Route::post('employee-overtimes/overtimes/multi-decline', [EmployeeOvertimeController::class, 'multiDecline'])->name('overtimes.multi-decline');
        Route::post('employee-overtimes/overtimes/multi-delete', [EmployeeOvertimeController::class, 'multiDelete'])->name('overtimes.multi-delete');

        // =====================================================
        // AJUSTES PROMOCIONES Y CAMBIOS DE PUESTO
        // =====================================================
        Route::get(
            'employee-salary-adjustments/filter-data',
            [EmployeeSalaryAdjustmentsController::class, 'filterData']
        );
        Route::get(
            'employee-salary-adjustments/filter-data-weekly',
            [EmployeeSalaryAdjustmentsController::class, 'filterDataWeekly']
        );
        Route::post(
            'employee-salary-adjustments/validate-salary',
            [EmployeeSalaryAdjustmentsController::class, 'validateSalary']
        )->name('employee-salary-adjustments.validate-salary');
        Route::get(
            'employee-salary-adjustments/{id}/get-format', 
            [EmployeeSalaryAdjustmentsController::class, 'getFormat']
        );
        Route::get(
            'employee-salary-adjustments/{id}/get-evaluation', 
            [EmployeeSalaryAdjustmentsController::class, 'getEvaluation']
        );
        Route::get(
            'employee-salary-adjustments/{id}/evaluation', 
            [EmployeeSalaryAdjustmentsController::class, 'evaluation']
        );
        Route::resource(
            'employee-salary-adjustments',
            EmployeeSalaryAdjustmentsController::class
        );

        // =====================================================
        // Busqueda de empleado
        // =====================================================

        Route::get('search-employee', [EmployeeSearchController::class, 'index']);
        Route::get('/{id?}/search-employee', [EmployeeSearchController::class, 'index']);

        // =====================================================
        //  Historial de Estados
        // =====================================================

        Route::resource('state-history', StateHistoryController::class)->names([
            'index' => 'state-history'
        ]);
    });

    // ===============================
    //  CATALOGS - CATALOGOS
    // ===============================
    Route::prefix('catalogs')->group(function () {

        // -------- Horarios --------
        Route::resource('schedules', ScheduleController::class);
        Route::post(
            'schedules/delete-multiple',
            [ScheduleController::class, 'destroyMultiple']
        )->name('schedules.destroy-multiple');

        // -------- Prestaciones --------
        Route::resource('benefits', BenefitsController::class);

        // -------- Empresas --------
        Route::resource('companies', CompanyController::class);

        // -------- Reglas --------
        Route::resource('policies', EmployeePolicyController::class)
            ->parameters(['policies' => 'policy']);

        // -------- Departamentos --------
        Route::resource('departments', DepartmentController::class)
            ->parameters(['departments' => 'department']);
        Route::post(
            'departments/delete-multiple',
            [DepartmentController::class, 'destroyMultiple']
        )->name('departments.destroy-multiple');
        Route::post(
            'departments/import',
            [DepartmentController::class, 'import']
        )->name('departments.import');
        Route::get(
            'departments/import/template',
            [DepartmentController::class, 'downloadTemplate']
        )->name('departments.import.template');

        // -------- Incidencias --------
        Route::resource('incidences', IncidencesController::class)
            ->names('catalogs.incidences');
        Route::post(
            'incidences/delete-multiple',
            [IncidencesController::class, 'destroyMultiple']
        )->name('catalogs.incidences.destroy-multiple');
        Route::post(
            'incidences/import',
            [IncidencesController::class, 'import']
        )->name('catalogs.incidences.import');
        Route::get(
            'incidences/import/template',
            [IncidencesController::class, 'downloadTemplate']
        )->name('catalogs.incidences.import.template');
        Route::post(
            'incidences/export',
            [IncidencesController::class, 'export']
        )->name('catalogs.incidences.export');

        // -------- Categorías de Incidencias --------
        Route::resource('category-incidences', CategoryIncidencesController::class);

        // -------- Razones De Estados --------
        Route::resource('status-reasons', StatusReasonsController::class);

        // -------- Percepciones y Deducciones --------
        Route::resource('earnings-deductions', EarningsDeductionsController::class)
            ->parameters(['earnings-deductions' => 'earningDeduction']);

        // -------- Tipos De Movimientos --------
        Route::resource('movement-types', MovementTypesController::class);

        // -------- Motivos --------
        Route::resource('reasons', ReasonsController::class);

        // -------- Rol de Turno --------
        Route::resource('shift-roles', ShiftRolesController::class);
        Route::post(
            'shift-roles/delete-multiple',
            [ShiftRolesController::class, 'destroyMultiple']
        )->name('shift-roles.destroy-multiple');

        // -------- Plantas --------
        Route::resource('branch-offices', BranchOfficesController::class);
        Route::post(
            'branch-offices/delete-multiple',
            [BranchOfficesController::class, 'destroyMultiple']
        )->name('branch-offices.destroy-multiple');

        // -------- Puestos --------
        Route::resource('positions', PositionsController::class);
        Route::post(
            'positions/delete-multiple',
            [PositionsController::class, 'destroyMultiple']
        )->name('positions.destroy-multiple');
        Route::post(
            'positions/import',
            [PositionsController::class, 'import']
        )->name('positions.import');
        Route::get(
            'positions/import/template',
            [PositionsController::class, 'downloadTemplate']
        )->name('positions.import.template');

        // -------- Clasificaciones --------
        Route::resource('classifications', ClassificationsController::class);

        // -------- Direcciones --------
        Route::resource('locations', LocationsController::class);

    });

    // Route::get('/status-reasons', [StatusReasonsController::class, 'Index'])->name('/status-reasons');
    // Route::post('/status-reasons', [StatusReasonsController::class, 'store'])->name('status-reasons.store');
    // Route::delete('/status-reasons/{statusReason}', [StatusReasonsController::class,'destroy'])->name('status-reasons.destroy');
    // Route::put('/status-reasons/{statusReason}', [StatusReasonsController::class,'update'])->name('status-reasons.update');

    //Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // =====================================================
    //  BRANCH OFFICES
    // =====================================================
    Route::get('/branch-offices-user', [BranchOfficeUserController::class, 'getBranchOfficesUser']);

    // =====================================================
    //  USUARIOS
    // =====================================================
    Route::get('users/users-search', [UserController::class, 'searchUser'])
        ->name('users.users-search');
    Route::resource('users', UserController::class)
        ->names([
            'index'   => '/users',
        ]);
    Route::post('/users/{user}/permissions/accept-all', [PermissionsController::class, 'acceptAll'])
        ->name('users.permissions.acceptAll');
    Route::post('/users/delete-multiple', [UserController::class, 'destroyMultiple'])
        ->name('users.delete-multiple');


    // =====================================================
    //  VISTAS
    // =====================================================
    Route::resource('views', ViewsController::class)
        ->names([
            'index' => '/views',
        ]);
    Route::post('/views/delete-multiple', [ViewsController::class, 'destroyMultiple'])->name('views.destroy-multiple');
    Route::post('/views/{view}/permissions/toggle', [UserController::class, 'toggle'])
    ->name('views.permissions.toggle');
    Route::post('/views/{view}/permissions/bulk', [UserController::class, 'bulk'])
    ->name('views.permissions.bulk');


    // =====================================================
    //  PERMISOS
    // =====================================================
    Route::resource('permissions', PermissionsController::class)
    ->names([
        'index' => '/permissions',
    ]);
    Route::patch('/permissions/by-view', [PermissionsController::class, 'updateByView'])
    ->name('permissions.updateByView');

    // routes/web.php
    Route::post('/users/{user}/permissions/save-overrides', [PermissionsController::class, 'saveOverrides'])
        ->name('users.permissions.save-overrides');


    Route::get('user/profile', [UserController::class, 'profile'])->name('user.profile');


    // FACTURACION

    Route::prefix('billing')->group(function () {

        Route::post('xml/send', [XmlController::class, 'sendDocuments'])->name('xml.send');
        Route::get('xml/filter-data', [XmlController::class, 'filter_data'])
        ->name('xml.filter-data');
        Route::resource('xml', XmlController::class);

        Route::get('/invoice-sat', [InvoiceSatController::class, 'index'])
        ->name('invoice-sat.index');
    });


    // NOMINAS
    Route::prefix('payroll')->group(function () {
        Route::resource('payroll-types', PayrollTypesController::class)->names(['index' => '/payroll-types',]);

        Route::post('/payroll-types/delete-multiple', [PayrollTypesController::class, 'destroyMultiple'])
        ->name('payroll-types.delete-multiple');

        Route::resource('payroll-accounts', PayrollAccountController::class)
        ->names(['index' => '/payroll-accounts',]);

        Route::resource('payroll-invoice-types', PayrollInvoiceTypesController::class)
        ->names(['index' => '/payroll-invoice-types',]);

        Route::get('payroll-departaments/payroll-h/{id}', [PayrollDepartamentsController::class, 'downloadDocumentHostinger']);

        Route::get('payroll-departaments/filter-data', [PayrollDepartamentsController::class, 'filter_data'])
        ->name('payroll-departaments.filter-data');

        Route::post('send-netsuite', [PayrollDepartamentsController::class, 'processPayrrol'])
        ->name('send-netsuite');

        Route::resource('payroll-departaments', PayrollDepartamentsController::class)->names([
            'index' => 'payroll-departaments'
        ]);

        Route::resource('payroll-invoices', PayrollInvoiceController::class)
        ->names([
            'index' => '/payroll-invoices',
        ]);

        Route::get('payroll-emails/filter-data', [EmailsController::class, 'filter_data'])
        ->name('payroll-emails.filter-data');
        Route::resource('payroll-emails', EmailsController::class)
        ->names([
            'index' => '/payroll-emails',
        ]);


        Route::get('payroll-invoices/invoice-h/{id}', [PayrollInvoiceController::class, 'downloadDocumentHostinger']);
        Route::get('payroll-invoices/invoice-d/{id}', [PayrollInvoiceController::class, 'downloadDocumentDigitalOcean']);
        Route::post('payroll-invoices/send-mail', [PayrollInvoiceController::class, 'queuMail']);
        Route::post('payroll-invoices/download', [PayrollInvoiceController::class, 'downloadDocuments']);
        Route::post('payroll-invoices/multiple-delete', [PayrollInvoiceController::class, 'multipleDestroy']);

        // -------- Percepciones y Deducciones Por Empleado --------
        Route::get(
            'payroll-earnings-deductions/filter-data',
            [PayrollEarningsDeductionsController::class, 'filterData']
        );

        Route::post(
            'payroll-earnings-deductions/delete-multiple',
            [PayrollEarningsDeductionsController::class, 'destroyMultiple']
        )->name('payroll-earnings-deductions.destroy-multiple');

        Route::resource('payroll-earnings-deductions', PayrollEarningsDeductionsController::class);

        Route::resource('employee-classes', EmployeeClassController::class);

    });


    Route::get('h2h/send', [H2HDocumentsController::class, 'sendDocuments'])
        ->name('h2h.send');
    Route::resource('h2h', H2HDocumentsController::class);



    Route::get('/hikvision/person', [HikcentralController::class, 'getPersonByCode']);

    Route::post('/users/{user}/permissions/save-all', [UserController::class, 'saveAll'])
    ->name('users.permissions.saveAll')
    ->middleware(['auth', 'verified']);

    Route::post('/users/{user}/permissions/save-all', [UserController::class, 'saveAll'])
    ->name('users.permissions.saveAll')
    ->middleware(['auth', 'verified']);

    // =====================================================
    //  Roles
    // =====================================================
    Route::resource('/roles', RoleController::class)->names([
        'index' => '/roles'
    ]);

    Route::post('/{role}/force-delete', [RoleController::class, 'forceDestroy'])
        ->name('force-destroy')
        ->middleware(['auth', 'can:roles.delete']);

    Route::get('/logs', [ActivityLogController::class, 'index'])
        ->name('logs.index');

    Route::get('/logs/{id}', [ActivityLogController::class, 'show'])
        ->name('logs.show');

    Route::get('/laravel-logs', [LaravelLogController::class, 'index'])
        ->name('laravel-logs.index');

    Route::get('/laravel-logs/{index}', [LaravelLogController::class, 'show'])
        ->name('laravel-logs.show');

    Route::post('/laravel-logs/clear', [LaravelLogController::class, 'clear'])
        ->name('laravel-logs.clear');

    Route::post('/chat', [ChatController::class, 'sendMessage']);

    


    Route::post('/user/set-default-branch', function (Request $request) {
        Auth::user()->update(['default_branch_id' => $request->branch_id]);
        return response()->json(['success' => true]);
    })->middleware('auth');

    Route::get('/user/default-branch', function () {
    $branchId = Auth::user()->default_branch_id;
    if ($branchId) {
        return \App\Models\BranchOffice::find($branchId);
    }
    return null;



})->middleware('auth');


});

// =====================================================
//  ARCHIVOS EN HOSTINGER
// =====================================================
Route::middleware('auth:sanctum')->group(function () {

    // Ver archivo
    Route::get('/files/view/{path}', [FileController::class, 'view'])
        ->where('path', '.*');

    // Descargar archivo
    Route::get('/files/download/{path}', [FileController::class, 'download'])
        ->where('path', '.*');

});



