<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\EmployeeDayVacation;
use App\Models\EmployeeDisability;
use App\Models\EmployeeIncidences;
use App\Models\EmployeeVacation;
use App\Models\Incidence;
use App\Models\Logs;
use App\Models\Schedules;
use App\Models\TxT;
use App\Models\User;
use App\Notifications\RegistroEditado;
use App\Notifications\RegistroEliminado;
use App\Notifications\RegistroGuardado;
use App\Models\UserEmpleado;
use App\Models\EmployeesParents;
use App\Notifications\IncidenceStatusNotification;
use App\Notifications\IncidenciaRegistrada;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Date;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeIncidencesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $incidences = Incidence::select('id','name')->where('requested_by_user', '=', '1')->get();
        $branchOfficeId = Employee::whereKey(Auth::id())->value('branch_office_id');
        
        return Inertia::render('Incidences/Index', [
            'incidences' => $incidences,
            'branchOfficeId' => $branchOfficeId,
        ]);
    }

    public function getIncidences(Request $request)
    {
        $week = $request->week ? $request->week : date('W');
        $year = $request->year ? $request->year : date('Y');
        $employee_id = Auth::id();
        $employee = Employee::select('branch_office_id')->where('id', $employee_id)->first();
        $incidences = EmployeeIncidences::getIncidences($employee->branch_office_id, $request->week, $request->year, $employee_id, $request->incidence_id, $request->eliminated);
        $lastWeekNumber = EmployeeIncidences::getLastWeekNumber($employee->branch_office_id);
        return json_encode(['incidences' => $incidences, 'lastWeekNumber' => $lastWeekNumber]);
    }

    public function createReport(string $id_incidence)
    {
        $incidence = EmployeeIncidences::getIncidenceById($id_incidence);

        $day_to_present = Carbon::parse($incidence[0]->hasta)
            ->addDay()
            ->format('d/m/Y');
        $vacaciones = EmployeeIncidences::getVacations($incidence[0]->employee_id, $incidence[0]->hasta);
        $final_total = $vacaciones->vacaciones_disponibles - $incidence[0]->dias;

        $data = [
            'folio' => $id_incidence,
            'empleado' => $incidence[0]->empleado,
            'empresa' => $incidence[0]->empresa,
            'departamento' => $incidence[0]->departamento,
            'puesto' => $incidence[0]->puesto,
            'fecha_ingreso' => $incidence[0]->fecha_ingreso,
            'fecha_solicitud' => $incidence[0]->fecha_solicitud,
            'dias' => $incidence[0]->dias,
            'desde' => $incidence[0]->desde,
            'hasta' => $incidence[0]->hasta,
            'presentarse' => $day_to_present,
            'saldo' => $final_total,
            'saldo_actual' => $vacaciones->vacaciones_disponibles,
            'hours_txt' => $incidence[0]->horas_txt,
            
        ];

        if($incidence[0]->id_incidencia == 23){
            $pdf = Pdf::loadView('pdf.incidenciaTxt', $data)
                ->setPaper('letter', 'portrait');

            return $pdf->download("TXT-{$id_incidence}.pdf");
        }else{
            $pdf = Pdf::loadView('pdf.incidencia', $data)
                ->setPaper('letter', 'portrait');

            return $pdf->download("VAC-{$id_incidence}.pdf");
        }
    }

    public function getIncidencesByEmployeeId()
    {
        $employee_id = Auth::id();
        $incidences = EmployeeIncidences::groupedForIndexByEmployeeId($employee_id);

        return json_encode($incidences);
    }

    public function getIncidencesDataLoad()
    {
        $employeeId = Auth::id();
        $employee = Employee::select('id', 'full_name', 'branch_office_id')->findOrFail($employeeId);
        $schedules = Schedules::select('id', 'name', 'entry_time', 'leave_time')->get();
        $allIncidences = Incidence::select(
            'id', 'name', 'description',
            'requires_document', 'requires_date', 'requires_schedule',
            'requires_rest_date', 'requires_code'
        )
            ->where('requested_by_user', 1)
            ->when($employee->branch_office_id != 19, fn ($query) => $query->whereNotIn('id', [12, 24, 25, 41, 72]))
            ->where('active', 1)
            ->get();

        return response()->json([
            'employees' => [$employee],
            'schedules' => $schedules,
            'allincidences' => $allIncidences,
            'lastWeekNumber' => EmployeeIncidences::getLastWeekNumber($employee->branch_office_id),
            'blockedWeeks' => EmployeeIncidences::getBlockedWeeks($employee->branch_office_id),
        ]);
    }

    private function getAllowedEmployeeIds(): ?array
    {
        $user = Auth::user();

        if (!$user || !(bool) $user->requires_filter) {
            return null;
        }

        $employeeId = Employee::where('user_id', $user->id)->value('id');

        if (!$employeeId) {
            return [];
        }

        return EmployeesParents::where('parent_id', $employeeId)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // public function getIncidencesDataLoad(Request $request){
    //     $employees = Employee::select("id","full_name", "branch_office_id")->
    //     where("branch_office_id", "=", $request->branch_office_id)->where("status", "!=", "termination")->orderBy('id', 'ASC')->get();
    //     $schedules = Schedules::select('id','name', 'entry_time', 'leave_time')->get();
    //     if($request->branch_office_id != 19){
    //         $allincidences = Incidence::select('id','name')->where('read_only', '=', '0')->where('active', '=', '1')->whereNotIn('id', [12,24,25,41,72])->get();
    //     }else{
    //         $allincidences = Incidence::select('id','name')->where('read_only', '=', '0')->where('active', '=', '1')->orWhere('id', 72)->get();
    //     }
    //     $lastWeekNumber = EmployeeIncidences::getLastWeekNumber($request->branch_office_id);

    //     return json_encode([
    //         'employees' => $employees,
    //         'schedules' => $schedules,
    //         'allincidences' => $allincidences,
    //         'lastWeekNumber' => $lastWeekNumber
    //     ]);
    // }

    /**
     * Show the form for creating a new resource.
     */
    public function getAssistanceData(Request $request){
        $employee_id = Auth::id();
        $employeeData = EmployeeIncidences::search_employee_data($request->date, $employee_id);

        return [
            'employeeData' => $employeeData
        ];
    }

    public function create(Request $request)
    {
        $employeeId = Auth::id();
        $employee = Employee::select('branch_office_id')->where('id', $employeeId)->first();

        // $schedule_name = EmployeeIncidences::getSchedule($employeeId);

        // $vacation = Schedules::select('vacations')->where('name', $schedule_name[0]->horario)->first();

        $days = 1;

        // if ($vacation && $vacation->vacations !== null) {
        //     $days *= (float) $vacation->vacations;
        // }

        
        return Inertia::render('Incidences/Create', [
            'employeeId' => $employeeId,
            'branchOfficeId' => $employee->branch_office_id,
            'vacations' => (float) $days
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $incidenceId = (int) $request->incidence_id;

        $getWeekData = function (string $date) {
            $dt = new DateTime($date);
            return [
                'week_number' => $dt->format('W'),
                'week_year'   => $dt->format('o'),
            ];
        };

        if ($incidenceId === 23) {
            $validated = $request->validate([
                'employee_id'            => 'required',
                'incidence_id'           => 'required',
                'singleDate'             => 'required',
                'txt_hours_to_register'  => 'required',
                'notes'                  => 'nullable',
                'schedule'               => 'required',
            ]);

            $employeeBranchOfficeId = $this->employeeBranchOfficeId((int) $validated['employee_id']);
            $week = $getWeekData($validated['singleDate']);
            $this->ensureWeekIsAvailable($employeeBranchOfficeId, $week);
            $automaticApproval = $this->blockedPeriodApprovalData(
                $employeeBranchOfficeId,
                $week
            );
            $hours = $validated['txt_hours_to_register'];

            $base = [
                "employee_id"       => $validated['employee_id'],
                "incidence_id"      => $validated['incidence_id'],
                "validity_from"     => $validated['singleDate'],
                "validity_to"       => $validated['singleDate'],
                "branch_office_id"  => $employeeBranchOfficeId,
                "comment"           => $validated['notes'],
                "days"              => null,
            ];

            $incidence = EmployeeIncidences::create(array_merge($base, $week, $automaticApproval, [
                "hours_txt"    => $hours,
                "schedule_id"  => $validated['schedule'],
            ]));

            Logs::create([
                    'action' => 'INSERT',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'relationship_id' => $incidence->id
                ]);
            $u = User::find(Auth::id(), 'id');
            $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

            $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));


            $this->notifyIncidenceRegistered($incidence);

            return $this->redirectAfterStore($request);
        }

        if ($incidenceId === 3) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'range'             => 'required',
                'notes'             => 'nullable',
                'days_to_register'  => 'required',
            ]);

            $employeeBranchOfficeId = $this->employeeBranchOfficeId((int) $validated['employee_id']);
            $week = $getWeekData($validated['range'][0]);
            $this->ensureWeekIsAvailable($employeeBranchOfficeId, $week);
            $automaticApproval = $this->blockedPeriodApprovalData(
                $employeeBranchOfficeId,
                $week
            );

            $incidence = EmployeeIncidences::create(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['range'][0],
                "validity_to"      => $validated['range'][1],
                "branch_office_id" => $employeeBranchOfficeId,
                "comment"          => $validated['notes'],
                "days"             => $validated['days_to_register'],
            ], $week, $automaticApproval));

            Logs::create([
                    'action' => 'INSERT',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'relationship_id' => $incidence->id
                ]);
            $u = User::find(Auth::id(), 'id');
            $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

            $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));


            $this->notifyIncidenceRegistered($incidence);

            return $this->redirectAfterStore($request);
        }

        return $this->storeConfiguredIncidence($request);

        // if (in_array($incidenceId, [20, 19], true)) {
        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'advance_date'      => 'required',
        //         'rest_date'         => 'required',
        //         'schedule'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //     ]);

        //     $week = $getWeekData($validated['rest_date']);

        //     $incidence = EmployeeIncidences::create(array_merge([
        //         "employee_id"      => $validated['employee_id'],
        //         "incidence_id"     => $validated['incidence_id'],
        //         "validity_from"    => $validated['advance_date'],
        //         "validity_to"      => $validated['advance_date'],
        //         "before_date"      => $validated['advance_date'],
        //         "rest_date"        => $validated['rest_date'],
        //         "branch_office_id" => $request->branch_office_id,
        //         "comment"          => $validated['notes'],
        //         "schedule_id"      => $validated['schedule'],
        //         "days"             => null
        //     ], $week));

        //     Logs::create([
        //             'action' => 'INSERT',
        //             'user_id' => Auth::id(),
        //             'table_name' => 'employee_incidences',
        //             'date' => Carbon::now(),
        //             'relationship_id' => $incidence->id
        //         ]);
        //     $u = User::find(Auth::id(), 'id');
        //     $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();


        //     $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));

        //     if($week['week_number'] < $request->lastWeekNumber){
        //         $branchOffice = BranchOffice::find($request->branch_office_id);
        //         $usersNom = is_string($branchOffice->users_nom_json)
        //             ? json_decode($branchOffice->users_nom_json, true)
        //             : $branchOffice->users_nom_json;

        //         if (!is_array($usersNom)) {
        //             $usersNom = [];
        //         }

        //         foreach ($usersNom as $userId) {
        //             $user = User::find($userId);
        //             $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidence->id, $e));
        //         }
        //     }

        //     $this->notifyIncidenceRegistered($incidence);

        //     return redirect()->route('incidences-employee.index');
        // }

        // $documentIncidences = [10,8,22,5,4,7,6,49,29,15];

        // if (in_array($incidenceId, $documentIncidences, true)) {

        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'document'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //         'document_number'   => 'required',
        //         'range'             => 'required',
        //         'days_to_register'  => 'required',
        //     ]);

        //     try {
        //         $disk = Storage::disk('remote_sftp');

        //         $dir = 'incidences/' . date('Y/m');
        //         $disk->makeDirectory($dir);

        //         $file = $request->file('document');
        //         $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
        //         $remotePath = $dir.'/'.$filename;

        //         $disk->put($remotePath, file_get_contents($file->getRealPath()));

        //         $week = $getWeekData($validated['range'][0]);

        //         $incidence = EmployeeIncidences::create(array_merge([
        //             "employee_id"       => $validated['employee_id'],
        //             "incidence_id"      => $validated['incidence_id'],
        //             "validity_from"     => $validated['range'][0],
        //             "validity_to"       => $validated['range'][1],
        //             "branch_office_id"  => $request->branch_office_id,
        //             "comment"           => $validated['notes'],
        //             "file_path"         => $remotePath,
        //             "document_number"   => $validated['document_number'],
        //             "days"             => $validated['days_to_register'],
        //         ], $week));

        //         Logs::create([
        //             'action' => 'INSERT',
        //             'user_id' => Auth::id(),
        //             'table_name' => 'employee_incidences',
        //             'date' => Carbon::now(),
        //             'relationship_id' => $incidence->id
        //         ]);
        //         $u = User::find(Auth::id(), 'id');
        //         $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        //         $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));

        //         if($week['week_number']  < $request->lastWeekNumber){
        //             $branchOffice = BranchOffice::find($request->branch_office_id);
        //             $usersNom = is_string($branchOffice->users_nom_json)
        //                 ? json_decode($branchOffice->users_nom_json, true)
        //                 : $branchOffice->users_nom_json;

        //             if (!is_array($usersNom)) {
        //                 $usersNom = [];
        //             }

        //             foreach ($usersNom as $userId) {
        //                 $user = User::find($userId);
        //                 $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidence->id, $e));
        //             }
        //         }

        //     } catch (\Throwable $e) {
        //         Log::error('SFTP failure', [
        //             'message' => $e->getMessage(),
        //             'root'    => config('filesystems.disks.remote_sftp.root'),
        //             'host'    => config('filesystems.disks.remote_sftp.host'),
        //         ]);
        //         throw $e;
        //     }

        //     $this->notifyIncidenceRegistered($incidence);



        //     return redirect()->route('incidences-employee.index');
        // }

        // $documentIncidencesNoNumber = [53,56,13,14];

        // if (in_array($incidenceId, $documentIncidencesNoNumber, true)) {

        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'document'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //         'range'             => 'required',
        //         'days_to_register'  => 'required',
        //     ]);

        //     try {
        //         $disk = Storage::disk('remote_sftp');

        //         $dir = 'incidences/' . date('Y/m');
        //         $disk->makeDirectory($dir);

        //         $file = $request->file('document');
        //         $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
        //         $remotePath = $dir.'/'.$filename;

        //         $disk->put($remotePath, file_get_contents($file->getRealPath()));

        //         $week = $getWeekData($validated['range'][0]);

        //         $incidence = EmployeeIncidences::create(array_merge([
        //             "employee_id"       => $validated['employee_id'],
        //             "incidence_id"      => $validated['incidence_id'],
        //             "validity_from"     => $validated['range'][0],
        //             "validity_to"       => $validated['range'][1],
        //             "branch_office_id"  => $request->branch_office_id,
        //             "comment"           => $validated['notes'],
        //             "file_path"         => $remotePath,
        //             "document_number"   => 'NA',
        //             "days"             => $validated['days_to_register'],
        //         ], $week));

        //         Logs::create([
        //             'action' => 'INSERT',
        //             'user_id' => Auth::id(),
        //             'table_name' => 'employee_incidences',
        //             'date' => Carbon::now(),
        //             'relationship_id' => $incidence->id
        //         ]);
        //         $u = User::find(Auth::id(), 'id');
        //         $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        //         $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));

        //         if($week['week_number']  < $request->lastWeekNumber){
        //             $branchOffice = BranchOffice::find($request->branch_office_id);
        //             $usersNom = is_string($branchOffice->users_nom_json)
        //                 ? json_decode($branchOffice->users_nom_json, true)
        //                 : $branchOffice->users_nom_json;

        //             if (!is_array($usersNom)) {
        //                 $usersNom = [];
        //             }

        //             foreach ($usersNom as $userId) {
        //                 $user = User::find($userId);
        //                 $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidence->id, $e));
        //             }
        //         }

        //     } catch (\Throwable $e) {
        //         Log::error('SFTP failure', [
        //             'message' => $e->getMessage(),
        //             'root'    => config('filesystems.disks.remote_sftp.root'),
        //             'host'    => config('filesystems.disks.remote_sftp.host'),
        //         ]);
        //         throw $e;
        //     }

        //     $this->notifyIncidenceRegistered($incidence);


        //     return redirect()->route('incidences-employee.index');
        // }

        // $validated = $request->validate([
        //     'employee_id'       => 'required',
        //     'incidence_id'      => 'required',
        //     'range'             => 'required',
        //     'days_to_register'  => 'required',
        //     'schedule'          => 'nullable',
        // ]);

        // $week = $getWeekData($validated['range'][0]);

        // $incidence = EmployeeIncidences::create(array_merge([
        //     "employee_id"      => $validated['employee_id'],
        //     "incidence_id"     => $validated['incidence_id'],
        //     "validity_from"    => $validated['range'][0],
        //     "validity_to"      => $validated['range'][1],
        //     "days"             => $validated['days_to_register'],
        //     "schedule_id"      => $validated['schedule'],
        //     "branch_office_id" => $request->branch_office_id,
        // ], $week));

        // Logs::create([
        //     'action' => 'INSERT',
        //     'user_id' => Auth::id(),
        //     'table_name' => 'employee_incidences',
        //     'date' => Carbon::now(),
        //     'relationship_id' => $incidence->id
        // ]);
        // $u = User::find(Auth::id(), 'id');
        // $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        // $u->notify(new RegistroGuardado('Incidencias por Empleado', $incidence->id, $e));

        // if($week['week_number']  < $request->lastWeekNumber){
        //     $branchOffice = BranchOffice::find($request->branch_office_id);
        //     $usersNom = is_string($branchOffice->users_nom_json)
        //         ? json_decode($branchOffice->users_nom_json, true)
        //         : $branchOffice->users_nom_json;

        //     if (!is_array($usersNom)) {
        //         $usersNom = [];
        //     }

        //     foreach ($usersNom as $userId) {
        //         $user = User::find($userId);
        //         $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidence->id, $e));
        //     }
        // }

        // $this->notifyIncidenceRegistered($incidence);

        // return redirect()->route('incidences-employee.index');
    }

    private function storeConfiguredIncidence(Request $request)
    {
        [$data, $week] = $this->configuredIncidenceData($request);
        $this->ensureWeekIsAvailable($data['branch_office_id'], $week);
        $automaticApproval = $this->blockedPeriodApprovalData(
            $data['branch_office_id'],
            $week
        );

        $incidence = EmployeeIncidences::create(array_merge($data, $week, $automaticApproval));

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'relationship_id' => $incidence->id,
        ]);

        $this->notifyEmployeeIncidenceChange(
            $incidence,
            new RegistroGuardado('Incidencias por Empleado', $incidence->id, $this->authenticatedEmployee())
        );
        $this->notifyIncidenceRegistered($incidence);

        return $this->redirectAfterDisabilitySave($incidence);
    }

    private function updateConfiguredIncidence(
        Request $request,
        EmployeeIncidences $employeeIncidence
    ) {
        [$data, $week] = $this->configuredIncidenceData($request, $employeeIncidence);
        $this->ensureWeekIsAvailable($data['branch_office_id'], $week);
        $oldData = $employeeIncidence->getOriginal();

        $employeeIncidence->update(array_merge($data, $week));

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'old_data' => json_encode($oldData),
            'relationship_id' => $employeeIncidence->id,
        ]);

        $this->notifyEmployeeIncidenceChange(
            $employeeIncidence,
            new RegistroEditado(
                'Incidencias por Empleado',
                $employeeIncidence->id,
                $this->authenticatedEmployee()
            )
        );

        return $this->redirectAfterDisabilitySave($employeeIncidence);
    }

    private function redirectAfterDisabilitySave(
        EmployeeIncidences $incidence
    ) {
        $disabilityIncidences = [8, 5, 4, 7, 6];

        if (in_array((int) $incidence->incidence_id, $disabilityIncidences, true)) {
            EmployeeDisability::firstOrCreate(
                ['employee_incidence_id' => $incidence->id],
                [
                    'incidence_id' => $incidence->incidence_id,
                    'status' => 'Pendiente envío',
                    'delivery_status' => 'Pendiente entrega',
                    'delivery_notification_status' => 'Sin notificar',
                ]
            );
        } else {
            EmployeeDisability::where('employee_incidence_id', $incidence->id)->delete();
        }

        return $this->redirectAfterStore();
    }

    private function configuredIncidenceData(
        Request $request,
        ?EmployeeIncidences $employeeIncidence = null
    ): array {
        $configuration = Incidence::findOrFail($request->integer('incidence_id'));
        $usesSpecialDates = $configuration->requires_date || $configuration->requires_rest_date;
        $hasExistingDocument = $employeeIncidence
            && (int) $employeeIncidence->incidence_id === (int) $configuration->id
            && $employeeIncidence->file_path;
        $documentIsRequired = $configuration->requires_document && ! $hasExistingDocument;
        $documentNumberRules = $configuration->requires_code
            ? [
                'required',
                'string',
                'size:8',
                'regex:/^[A-Za-z0-9]{8}$/',
                Rule::unique('employee_incidences', 'document_number')
                    ->where(fn ($query) => $query->whereNull('deleted_at'))
                    ->ignore($employeeIncidence?->id),
            ]
            : ['nullable', 'string', 'max:255'];

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'incidence_id' => ['required', 'exists:incidences,id'],
            'notes' => ['nullable', 'string'],
            'range' => [$usesSpecialDates ? 'nullable' : 'required', 'nullable', 'array', 'size:2'],
            'range.0' => [$usesSpecialDates ? 'nullable' : 'required', 'nullable', 'date'],
            'range.1' => [$usesSpecialDates ? 'nullable' : 'required', 'nullable', 'date'],
            'days_to_register' => [$usesSpecialDates ? 'nullable' : 'required', 'nullable', 'numeric', 'min:0'],
            'advance_date' => [$configuration->requires_date ? 'required' : 'nullable', 'nullable', 'date'],
            'rest_date' => [$configuration->requires_rest_date ? 'required' : 'nullable', 'nullable', 'date'],
            'schedule' => [$configuration->requires_schedule ? 'required' : 'nullable', 'nullable', 'exists:schedules,id'],
            'document' => [$documentIsRequired ? 'required' : 'nullable', 'nullable', 'file'],
            'document_number' => $documentNumberRules,
        ], [
            'document_number.required' => 'El folio es requerido.',
            'document_number.size' => 'El folio debe tener exactamente 8 caracteres.',
            'document_number.regex' => 'El folio sÃ³lo puede contener letras y nÃºmeros.',
            'document_number.unique' => 'Ya existe otra incidencia con este folio.',
        ]);

        $employeeBranchOfficeId = $this->employeeBranchOfficeId((int) $validated['employee_id']);

        if ($usesSpecialDates) {
            $validityFrom = $configuration->requires_date
                ? $validated['advance_date']
                : $validated['rest_date'];
            $validityTo = $configuration->requires_rest_date
                ? $validated['rest_date']
                : $validityFrom;
        } else {
            [$validityFrom, $validityTo] = $validated['range'];
        }

        $filePath = $configuration->requires_document && $hasExistingDocument
            ? $employeeIncidence?->file_path
            : null;

        if ($configuration->requires_document && $request->hasFile('document')) {
            $filePath = $this->uploadIncidenceDocument($request);
        }

        $data = [
            'employee_id' => $validated['employee_id'],
            'incidence_id' => $configuration->id,
            'validity_from' => $validityFrom,
            'validity_to' => $validityTo,
            'days' => $usesSpecialDates ? null : $validated['days_to_register'],
            'branch_office_id' => $employeeBranchOfficeId,
            'comment' => $validated['notes'] ?? null,
            'before_date' => $configuration->requires_date
                ? $validated['advance_date']
                : null,
            'rest_date' => $configuration->requires_rest_date
                ? $validated['rest_date']
                : null,
            'schedule_id' => $configuration->requires_schedule
                ? $validated['schedule']
                : null,
            'file_path' => $filePath,
            'document_number' => $configuration->requires_code
                ? strtoupper(trim($validated['document_number']))
                : null,
            'hours_txt' => null,
        ];

        $weekDate = $configuration->requires_rest_date
            ? $validated['rest_date']
            : $validityFrom;
        $date = new DateTime($weekDate);

        return [$data, [
            'week_number' => $date->format('W'),
            'week_year' => $date->format('o'),
        ]];
    }

    private function ensureWeekIsAvailable(int $branchOfficeId, array $week): void
    {



        $currentWeek = Carbon::now('America/Mexico_City')->startOfWeek();
        $requestedWeek = Carbon::now('America/Mexico_City')
            ->setISODate((int) $week['week_year'], (int) $week['week_number'])
            ->startOfWeek();

        if ($requestedWeek->greaterThanOrEqualTo($currentWeek)) {
            return;
        }

        throw ValidationException::withMessages([
            'week' => 'Solo puedes registrar incidencias de la semana actual en adelante.',
        ]);
    }

    private function employeeBranchOfficeId(int $employeeId): int
    {
        $branchOfficeId = Employee::whereKey($employeeId)->value('branch_office_id');

        if (! $branchOfficeId) {
            throw ValidationException::withMessages([
                'employee_id' => 'El empleado no tiene una planta asignada.',
            ]);
        }

        return (int) $branchOfficeId;
    }

    private function redirectAfterStore()
    {
        return redirect()->route('incidences-employee.index');
    }

    private function blockedPeriodApprovalData(int $branchOfficeId, array $week): array
    {
        if (EmployeeIncidences::isWeekAvailable(
            $branchOfficeId,
            (int) $week['week_number'],
            (int) $week['week_year']
        )) {
            return [];
        }

        return [
            'approved_at' => Carbon::now('America/Mexico_City'),
            'approved_by' => Auth::id(),
            'approved_in_blocked_period' => true,
        ];
    }

    private function uploadIncidenceDocument(Request $request): string
    {
        $disk = Storage::disk('remote_sftp');
        $directory = 'incidences/' . date('Y/m');
        $disk->makeDirectory($directory);

        $file = $request->file('document');
        $filename = uniqid('inc_', true) . '.' . $file->getClientOriginalExtension();
        $remotePath = $directory . '/' . $filename;
        $disk->put($remotePath, file_get_contents($file->getRealPath()));

        return $remotePath;
    }

    private function authenticatedEmployee(): ?Employee
    {
        return Employee::select('id', 'branch_office_id', 'full_name')
            ->where('user_id', Auth::id())
            ->first();
    }

    private function notifyEmployeeIncidenceChange(
        EmployeeIncidences $incidence,
        object $notification
    ): void {
        $user = User::find(Auth::id());

        if ($user) {
            $user->notify($notification);
        }
    }



    private function notifyIncidenceRegistered(EmployeeIncidences $incidence): void
    {
        $employee = Employee::find($incidence->employee_id);

        if (! $employee || ! $employee->employee_parent_id) {
            return;
        }

        $parentIds = array_map('trim', explode(',', $employee->employee_parent_id));

        foreach ($parentIds as $parentId) {
            if ($parentId === '') {
                continue;
            }

            $parent = Employee::find($parentId);
            $user = $parent?->user_id ? User::find($parent->user_id) : null;

            if ($user) {
                $user->notify(new IncidenciaRegistrada($incidence->id, $employee->id, $employee, $incidence));
            }
        }
    }

    public function downloadDocument($id)
    {
        $incidence = EmployeeIncidences::findOrFail($id);

        if (!$incidence->file_path) {
            abort(404, 'Esta incidencia no tiene un documento adjunto.');
        }

        $disk = Storage::disk('remote_sftp');

        if (!$disk->exists($incidence->file_path)) {
            abort(404, 'El archivo no se encontrÃ³ en el servidor remoto.');
        }
        return $disk->response($incidence->file_path);
    }


    /**
     * Display the specified resource.
     */
    public function show(EmployeeIncidences $incidences_employee)
    {
        $incidenceData = EmployeeIncidences::getIncidenceData($incidences_employee->id);
        return Inertia::render('Incidences/Show', [
            'incidence' => $incidenceData
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeIncidences $incidences_employee)
    {
        $employeeData = Employee::where('id', $incidences_employee->employee_id)->first();
        $schedules = Schedules::select('id','name', 'entry_time', 'leave_time')->get();
        if($employeeData->branch_office_id != 19){
            $allincidences = Incidence::select(
                'id', 'name', 'description',
                'requires_document', 'requires_date', 'requires_schedule',
                'requires_rest_date', 'requires_code'
            )->where('read_only', '=', '0')->where('active', '=', '1')->whereNotIn('id', [12,24,25,41,72])->get();
        }else{
            $allincidences = Incidence::select(
                'id', 'name', 'description',
                'requires_document', 'requires_date', 'requires_schedule',
                'requires_rest_date', 'requires_code'
            )->where('read_only', '=', '0')->where('active', '=', '1')->orWhere('id', 72)->get();
        }

        return Inertia::render('Incidences/Edit', [
            'incidence' => $incidences_employee,
            'employeeData' => $employeeData,
            'schedules' => $schedules,
            'allincidences' => $allincidences,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeIncidences $incidences_employee)
    {
        $incidenceId = (int) $request->incidence_id;

        $getWeekData = function (string $date) {
            $dt = new DateTime($date);
            return [
                'week_number' => $dt->format('W'),
                'week_year'   => $dt->format('o'),
            ];
        };

        if ($incidenceId === 23) {
            $validated = $request->validate([
                'employee_id'            => 'required',
                'incidence_id'           => 'required',
                'singleDate'             => 'required',
                'txt_hours_to_register'  => 'required',
                'notes'                  => 'nullable',
                'schedule'               => 'required',
            ]);

            $employeeBranchOfficeId = $this->employeeBranchOfficeId((int) $validated['employee_id']);
            $week = $getWeekData($validated['singleDate']);
            $this->ensureWeekIsAvailable($employeeBranchOfficeId, $week);
            $hours = $validated['txt_hours_to_register'];

            $base = [
                "employee_id"       => $validated['employee_id'],
                "incidence_id"      => $validated['incidence_id'],
                "validity_from"     => $validated['singleDate'],
                "validity_to"       => $validated['singleDate'],
                "branch_office_id"  => $employeeBranchOfficeId,
                "comment"           => $validated['notes'],
                "days"              => null,
            ];

            $oldData = $incidences_employee->getOriginal();

            $incidences_employee->update(array_merge($base, $week, [
                "hours_txt"    => $hours,
                "schedule_id"  => $validated['schedule'],
            ]));

            Logs::create([
                'action' => 'UPDATE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_incidences',
                'date' => Carbon::now(),
                'old_data' => json_encode($oldData),
                'relationship_id' => $incidences_employee->id
            ]);

            $u = User::find(Auth::id(), 'id');
            $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

            $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));


            return redirect()->route('incidences-employee.index');
        }

        if ($incidenceId === 3) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'range'             => 'required',
                'notes'             => 'nullable',
                'days_to_register'  => 'required',
            ]);

            $employeeBranchOfficeId = $this->employeeBranchOfficeId((int) $validated['employee_id']);
            $week = $getWeekData($validated['range'][0]);
            $this->ensureWeekIsAvailable($employeeBranchOfficeId, $week);

            $oldData = $incidences_employee->getOriginal();

            $incidences_employee->update(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['range'][0],
                "validity_to"      => $validated['range'][1],
                "branch_office_id" => $employeeBranchOfficeId,
                "comment"          => $validated['notes'],
                "days"             => $validated['days_to_register'],
            ], $week));


            Logs::create([
                'action' => 'UPDATE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_incidences',
                'date' => Carbon::now(),
                'old_data' => json_encode($oldData),
                'relationship_id' => $incidences_employee->id
            ]);

            $u = User::find(Auth::id(), 'id');
            $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

            $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));


            return redirect()->route('incidences-employee.index');
        }

        return $this->updateConfiguredIncidence($request, $incidences_employee);

        // if (in_array($incidenceId, [20, 19], true)) {
        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'advance_date'      => 'required',
        //         'rest_date'         => 'required',
        //         'schedule'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //     ]);

        //     $week = $getWeekData($validated['rest_date']);

        //     $oldData = $incidences_employee->getOriginal();

        //     $incidences_employee->update(array_merge([
        //         "employee_id"      => $validated['employee_id'],
        //         "incidence_id"     => $validated['incidence_id'],
        //         "validity_from"    => $validated['advance_date'],
        //         "validity_to"      => $validated['advance_date'],
        //         "before_date"      => $validated['advance_date'],
        //         "rest_date"        => $validated['rest_date'],
        //         "branch_office_id" => $request->branch_office_id,
        //         "comment"          => $validated['notes'],
        //         "schedule_id"      => $validated['schedule'],
        //         "days"             => null
        //     ], $week));

        //     Logs::create([
        //         'action' => 'UPDATE',
        //         'user_id' => Auth::id(),
        //         'table_name' => 'employee_incidences',
        //         'date' => Carbon::now(),
        //         'old_data' => json_encode($oldData),
        //         'relationship_id' => $incidences_employee->id
        //     ]);

        //     $u = User::find(Auth::id(), 'id');
        //     $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        //     $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));

        //     if($week['week_number'] < $request->lastWeekNumber){
        //         $branchOffice = BranchOffice::find($request->branch_office_id);
        //         $usersNom = is_string($branchOffice->users_nom_json)
        //             ? json_decode($branchOffice->users_nom_json, true)
        //             : $branchOffice->users_nom_json;

        //         if (!is_array($usersNom)) {
        //             $usersNom = [];
        //         }

        //         foreach ($usersNom as $userId) {
        //             $user = User::find($userId);
        //             $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidences_employee->id, $e));
        //         }
        //     }

        //     return redirect()->route('incidences-employee.index');
        // }

        // $documentIncidences = [10,8,22,5,4,7,6,49,29,14,15];

        // if (in_array($incidenceId, $documentIncidences, true)) {
        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'document'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //         'document_number'   => 'required',
        //         'range'             => 'required',
        //     ]);

        //     try {
        //         $disk = Storage::disk('remote_sftp');

        //         $dir = 'incidences/' . date('Y/m');
        //         $disk->makeDirectory($dir);

        //         $file = $request->file('document');
        //         $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
        //         $remotePath = $dir.'/'.$filename;

        //         $disk->put($remotePath, file_get_contents($file->getRealPath()));

        //         $week = $getWeekData($validated['range'][0]);

        //         $oldData = $incidences_employee->getOriginal();

        //         $incidences_employee->update(array_merge([
        //             "employee_id"       => $validated['employee_id'],
        //             "incidence_id"      => $validated['incidence_id'],
        //             "validity_from"     => $validated['range'][0],
        //             "validity_to"       => $validated['range'][1],
        //             "branch_office_id"  => $request->branch_office_id,
        //             "comment"           => $validated['notes'],
        //             "file_path"         => $remotePath,
        //             "document_number"   => $validated['document_number'],
        //             "days"              => null,
        //         ], $week));

        //         Logs::create([
        //             'action' => 'UPDATE',
        //             'user_id' => Auth::id(),
        //             'table_name' => 'employee_incidences',
        //             'date' => Carbon::now(),
        //             'old_data' => json_encode($oldData),
        //             'relationship_id' => $incidences_employee->id
        //         ]);

        //         $u = User::find(Auth::id(), 'id');
        //         $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        //         $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));

        //         if($week['week_number'] < $request->lastWeekNumber){
        //             $branchOffice = BranchOffice::find($request->branch_office_id);
        //             $usersNom = is_string($branchOffice->users_nom_json)
        //                 ? json_decode($branchOffice->users_nom_json, true)
        //                 : $branchOffice->users_nom_json;

        //             if (!is_array($usersNom)) {
        //                 $usersNom = [];
        //             }

        //             foreach ($usersNom as $userId) {
        //                 $user = User::find($userId);
        //                 $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidences_employee->id, $e));
        //             }
        //         }

        //     } catch (\Throwable $e) {
        //         Log::error('SFTP failure', [
        //             'message' => $e->getMessage(),
        //             'root'    => config('filesystems.disks.remote_sftp.root'),
        //             'host'    => config('filesystems.disks.remote_sftp.host'),
        //         ]);
        //         throw $e;
        //     }

        //     return redirect()->route('incidences-employee.index');
        // }

        // $documentIncidencesNoNumber = [53,56,13];

        // if (in_array($incidenceId, $documentIncidencesNoNumber, true)) {

        //     $validated = $request->validate([
        //         'employee_id'       => 'required',
        //         'incidence_id'      => 'required',
        //         'document'          => 'required',
        //         'branch_office_id'  => 'required',
        //         'notes'             => 'nullable',
        //         'range'             => 'required',
        //         'days_to_register'  => 'required',
        //     ]);

        //     try {
        //         $disk = Storage::disk('remote_sftp');

        //         $dir = 'incidences/' . date('Y/m');
        //         $disk->makeDirectory($dir);

        //         $file = $request->file('document');
        //         $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
        //         $remotePath = $dir.'/'.$filename;

        //         $disk->put($remotePath, file_get_contents($file->getRealPath()));

        //         $oldData = $incidences_employee->getOriginal();

        //         $week = $getWeekData($validated['range'][0]);

        //         $incidences_employee->update(array_merge([
        //             "employee_id"       => $validated['employee_id'],
        //             "incidence_id"      => $validated['incidence_id'],
        //             "validity_from"     => $validated['range'][0],
        //             "validity_to"       => $validated['range'][1],
        //             "branch_office_id"  => $request->branch_office_id,
        //             "comment"           => $validated['notes'],
        //             "file_path"         => $remotePath,
        //             "document_number"   => $validated['document_number'],
        //             "days"              => null,
        //         ], $week));

        //         Logs::create([
        //             'action' => 'UPDATE',
        //             'user_id' => Auth::id(),
        //             'table_name' => 'employee_incidences',
        //             'date' => Carbon::now(),
        //             'old_data' => json_encode($oldData),
        //             'relationship_id' => $incidences_employee->id
        //         ]);

        //         $u = User::find(Auth::id(), 'id');
        //         $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        //         $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));

        //         if($week['week_number'] < $request->lastWeekNumber){
        //             $branchOffice = BranchOffice::find($request->branch_office_id);
        //             $usersNom = is_string($branchOffice->users_nom_json)
        //                 ? json_decode($branchOffice->users_nom_json, true)
        //                 : $branchOffice->users_nom_json;

        //             if (!is_array($usersNom)) {
        //                 $usersNom = [];
        //             }

        //             foreach ($usersNom as $userId) {
        //                 $user = User::find($userId);
        //                 $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidences_employee->id, $e));
        //             }
        //         }

        //     } catch (\Throwable $e) {
        //         Log::error('SFTP failure', [
        //             'message' => $e->getMessage(),
        //             'root'    => config('filesystems.disks.remote_sftp.root'),
        //             'host'    => config('filesystems.disks.remote_sftp.host'),
        //         ]);
        //         throw $e;
        //     }



        //     return redirect()->route('incidences-employee.index');
        // }

        // $validated = $request->validate([
        //     'employee_id'       => 'required',
        //     'incidence_id'      => 'required',
        //     'range'             => 'required',
        //     'days_to_register'  => 'required',
        //     'schedule'          => 'nullable',
        // ]);

        // $oldData = $incidences_employee->getOriginal();

        // $week = $getWeekData($validated['range'][0]);

        // $incidences_employee->update(array_merge([
        //     "employee_id"      => $validated['employee_id'],
        //     "incidence_id"     => $validated['incidence_id'],
        //     "validity_from"    => $validated['range'][0],
        //     "validity_to"      => $validated['range'][1],
        //     "days"             => $validated['days_to_register'],
        //     "branch_office_id" => $request->branch_office_id,
        //     "schedule_id"      => $validated['schedule'],
        // ], $week));

        // Logs::create([
        //     'action' => 'UPDATE',
        //     'user_id' => Auth::id(),
        //     'table_name' => 'employee_incidences',
        //     'date' => Carbon::now(),
        //     'old_data' => json_encode($oldData),
        //     'relationship_id' => $incidences_employee->id
        // ]);

        // $u = User::find(Auth::id(), 'id');
        // $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        // $u->notify(new RegistroEditado('Incidencias por Empleado', $incidences_employee->id, $e));

        // if($week['week_number'] < $request->lastWeekNumber){
        //     $branchOffice = BranchOffice::find($request->branch_office_id);
        //     $usersNom = is_string($branchOffice->users_nom_json)
        //         ? json_decode($branchOffice->users_nom_json, true)
        //         : $branchOffice->users_nom_json;

        //     if (!is_array($usersNom)) {
        //         $usersNom = [];
        //     }

        //     foreach ($usersNom as $userId) {
        //         $user = User::find($userId);
        //         $user->notify(new RegistroFueraSemana('Incidencias por Empleado', $incidences_employee->id, $e));
        //     }
        // }

        // return redirect()->route('incidences-employee.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeIncidences $incidences_employee)
    {
        $incidences_employee->update([
            "deleted_by" => Auth::user()->id,
            "deleted_at" => Carbon::now()
        ]);

        EmployeeDisability::where(
            'employee_incidence_id',
            $incidences_employee->id
        )->delete();

        if($incidences_employee->incidence_id == 23){
            TxT::where('employee_incidence_id', $incidences_employee->id)->delete();
        }

        if($incidences_employee->incidence_id == 3){
            EmployeeDayVacation::where('employee_incidence_id', $incidences_employee->id)->delete();
        }

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'relationship_id' => $incidences_employee->id
        ]);

        $u = User::find(Auth::id(), 'id');
        $e = Employee::select('id', 'branch_office_id', 'full_name')->where('user_id', $u->id)->first();

        $u->notify(new RegistroEliminado('Incidencias por Empleado', $incidences_employee->id, $e));


        return redirect()->route('incidences-employee.index');
    }

    public function approve(EmployeeIncidences $incidence, Request $request)
    {



        $incidence->update([
            "approved_at" => Carbon::now('America/Mexico_City'),
            "approved_by" => Auth::user()->id,
            "approved_in_blocked_period" => false,
            "declined_at" => null,
            "declined_by" => null,
        ]);

        EmployeeIncidences::syncApprovedBalances($incidence);

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now('America/Mexico_City'),
            'relationship_id' => $incidence->id
        ]);

        $employee = Employee::find($incidence->employee_id);


        if ($employee && $employee->user_id) {

            $user = UserEmpleado::find($employee->user_id);

            if ($user) {

                $user->notify(
                    new IncidenceStatusNotification(
                        'APPROVED',
                        Incidence::find($incidence->incidence_id)?->name ?? 'Incidencia',
                        $employee,
                        $incidence->id,
                        $incidence
                    )
                );
            }
        }

        return redirect()->route('incidences-employee.index');
    }

    public function reject(EmployeeIncidences $incidence)
    {
        $incidence->update([
            "declined_at" => Carbon::now('America/Mexico_City'),
            "declined_by" => Auth::user()->id,
        ]);
        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now('America/Mexico_City'),
            'relationship_id' => $incidence->id
        ]);


        $employee = Employee::find($incidence->employee_id);

        if ($employee && $employee->user_id) {

            $user = UserEmpleado::find($employee->user_id);

            if ($user) {

                $user->notify(
                    new IncidenceStatusNotification(
                        'REJECTED',
                        Incidence::find($incidence->incidence_id)?->name ?? 'Incidencia',
                        $employee,
                        $incidence->id,
                        $incidence
                    )
                );
            }
        }

        return redirect()->route('incidences-employee.index');
    }

    public function rejectAll(Request $request)
    {
        $ids = $request->ids;
        $today = Carbon::now('America/Mexico_City');
        EmployeeIncidences::whereIn('id', $ids)->update([
            "declined_at" => $today,
            "declined_by" => Auth::user()->id,
        ]);

        $records = EmployeeIncidences::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DECLINED',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now('America/Mexico_City'),
                'relationship_id' => $record->id
            ]);

            $employee = Employee::find($record->employee_id);

            if ($employee && $employee->user_id) {

                $user = UserEmpleado::find($employee->user_id);

                if ($user) {

                    $user->notify(
                        new IncidenceStatusNotification(
                            'REJECTED',
                            Incidence::find($record->incidence_id)?->name ?? 'Incidencia',
                            $employee,
                            $record->id,
                            $record
                        )
                    );
                }
            }
        }

        return redirect()->route('incidences-employee.index');
    }

    public function approveAll(Request $request)
    {
        $today = Carbon::now('America/Mexico_City');
        $user = Auth::user();

        $incidences = EmployeeIncidences::whereIn('id', $request->ids)->get();

        foreach ($incidences as $incidence) {
            $incidence->update([
                "approved_at" => $today,
                "approved_by" => $user->id,
            ]);

            EmployeeIncidences::syncApprovedBalances($incidence);

            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now('America/Mexico_City'),
                'relationship_id' => $incidence->id
            ]);

            $employee = Employee::find($incidence->employee_id);

            if ($employee && $employee->user_id) {

                $userNotification = UserEmpleado::find($employee->user_id);

                if ($userNotification) {

                    $userNotification->notify(
                        new IncidenceStatusNotification(
                            'APPROVED',
                            Incidence::find($incidence->incidence_id)?->name ?? 'Incidencia',
                            $employee,
                            $incidence->id,
                            $incidence
                        )
                    );
                }
            }
        }

        return redirect()->route('incidences-employee.index');
    }

    public function deleteAll(Request $request)
    {
        $ids = $request->ids;
        EmployeeIncidences::whereIn('id', $ids)->update([
            "deleted_by" => Auth::user()->id,
            "deleted_at" => Carbon::now('America/Mexico_City')
        ]);

        $records = EmployeeIncidences::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DELETE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_incidences',
                'date' => Carbon::now('America/Mexico_City'),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('incidences-employee.index');
    }

}
