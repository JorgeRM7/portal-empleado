<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Employee;
use App\Models\EmployeeDayVacation;
use App\Models\EmployeeIncidences;
use App\Models\EmployeeVacation;
use App\Models\Incidence;
use App\Models\Logs;
use App\Models\Schedules;
use App\Models\TxT;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Date;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeeIncidencesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $incidences = Incidence::select('id','name')->get();
        $branchOffices = BranchOffice::select('id','code')->get();
        $employees = Employee::select('id','full_name', 'branch_office_id')->get();
        
        return Inertia::render('Incidences/Index', [
            'incidences' => $incidences,
            'branchOffices' => $branchOffices,
            'employees' => $employees
        ]);
    }

    public function getIncidences(Request $request)
    {
        $week = $request->week ? $request->week : date('W');
        $year = $request->year ? $request->year : date('Y');
        $incidences = EmployeeIncidences::getIncidences($request->branch_office_id, $request->week, $request->year, $request->employee_id, $request->incidence_id, $request->eliminated);
        $lastWeekNumber = EmployeeIncidences::getLastWeekNumber($request->branch_office_id);
        return json_encode(['incidences' => $incidences, 'lastWeekNumber' => $lastWeekNumber]);
    }

    public function createReport(string $id_incidence)
    {
        $incidence = EmployeeIncidences::getIncidenceById($id_incidence);

        $day_to_present = Carbon::parse($incidence[0]->hasta)
            ->addDay()
            ->format('d/m/Y');

        $final_total = $incidence[0]->saldo_inicial - $incidence[0]->dias;

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
            'saldo_actual' => $incidence[0]->saldo_inicial,
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

    public function getIncidencesByEmployeeId(Request $request)
    {
        $incidences = EmployeeIncidences::groupedForIndexByEmployeeId($request->employee_id);

        return json_encode($incidences);
    }

    public function getIncidencesDataLoad(Request $request){
        $employees = Employee::select("id","full_name", "branch_office_id")->
        where("branch_office_id", "=", $request->branch_office_id)->where("status", "!=", "termination")->orderBy('id', 'ASC')->get();
        $schedules = Schedules::select('id','name', 'entry_time', 'leave_time')->get();
        if($request->branch_office_id != 19){
            $allincidences = Incidence::select('id','name')->where('read_only', '=', '0')->where('active', '=', '1')->whereNotIn('id', [12,24,25,41])->get();
        }else{
            $allincidences = Incidence::select('id','name')->where('read_only', '=', '0')->where('active', '=', '1')->get();
        }
        $lastWeekNumber = EmployeeIncidences::getLastWeekNumber($request->branch_office_id);

        return json_encode([
            'employees' => $employees,
            'schedules' => $schedules,
            'allincidences' => $allincidences,
            'lastWeekNumber' => $lastWeekNumber
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
        return Inertia::render('Incidences/Create');
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
                'week_year'   => $dt->format('Y'),
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

            $week = $getWeekData($validated['singleDate']);
            $hours = (int) $validated['txt_hours_to_register'];

            $base = [
                "employee_id"       => $validated['employee_id'],
                "incidence_id"      => $validated['incidence_id'],
                "validity_from"     => $validated['singleDate'],
                "validity_to"       => $validated['singleDate'],
                "branch_office_id"  => $request->branch_office_id,
                "comment"           => $validated['notes'],
                "days"              => null,
            ];

            $incidence = EmployeeIncidences::create(array_merge($base, $week, [
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

            return redirect()->route('/incidences-employee');
        }

        if ($incidenceId === 3) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'range'             => 'required',
                'notes'             => 'nullable',
                'days_to_register'  => 'required',
            ]);

            $week = $getWeekData($validated['range'][0]);

            $incidence = EmployeeIncidences::create(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['range'][0],
                "validity_to"      => $validated['range'][1],
                "branch_office_id" => $request->branch_office_id,
                "comment"          => $validated['notes'],
                "days"             => $validated['days_to_register'],
            ], $week));

            Logs::create([
                    'action' => 'INSERT',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'relationship_id' => $incidence->id
                ]);

            return redirect()->route('/incidences-employee');
        }

        if (in_array($incidenceId, [20, 19], true)) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'advance_date'      => 'required',
                'rest_date'         => 'required',
                'schedule'          => 'required',
                'branch_office_id'  => 'required',
                'notes'             => 'nullable',
            ]);

            $week = $getWeekData($validated['advance_date']);

            $incidence = EmployeeIncidences::create(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['advance_date'],
                "validity_to"      => $validated['advance_date'],
                "before_date"      => $validated['advance_date'],
                "rest_date"        => $validated['rest_date'],
                "branch_office_id" => $request->branch_office_id,
                "comment"          => $validated['notes'],
                "schedule_id"      => $validated['schedule'],
                "days"             => null
            ], $week));

            Logs::create([
                    'action' => 'INSERT',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'relationship_id' => $incidence->id
                ]);

            return redirect()->route('/incidences-employee');
        }

        $documentIncidences = [53,10,8,22,56,5,4,7,6,49,29,14,15,13];

        if (in_array($incidenceId, $documentIncidences, true)) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'document'          => 'required',
                'branch_office_id'  => 'required',
                'notes'             => 'nullable',
                'document_number'   => 'required',
                'range'             => 'required',
                'days_to_register'  => 'required',
            ]);

            try {
                $disk = Storage::disk('remote_sftp');

                $dir = 'incidences/' . date('Y/m');
                $disk->makeDirectory($dir);

                $file = $request->file('document');
                $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
                $remotePath = $dir.'/'.$filename;

                $disk->put($remotePath, file_get_contents($file->getRealPath()));

                $week = $getWeekData($validated['range'][0]);

                $incidence = EmployeeIncidences::create(array_merge([
                    "employee_id"       => $validated['employee_id'],
                    "incidence_id"      => $validated['incidence_id'],
                    "validity_from"     => $validated['range'][0],
                    "validity_to"       => $validated['range'][1],
                    "branch_office_id"  => $request->branch_office_id,
                    "comment"           => $validated['notes'],
                    "file_path"         => $remotePath,
                    "document_number"   => $validated['document_number'],
                    "days"             => $validated['days_to_register'],
                ], $week));

                Logs::create([
                    'action' => 'INSERT',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'relationship_id' => $incidence->id
                ]);

            } catch (\Throwable $e) {
                Log::error('SFTP failure', [
                    'message' => $e->getMessage(),
                    'root'    => config('filesystems.disks.remote_sftp.root'),
                    'host'    => config('filesystems.disks.remote_sftp.host'),
                ]);
                throw $e;
            }

            

            return redirect()->route('/incidences-employee');
        }

        $validated = $request->validate([
            'employee_id'       => 'required',
            'incidence_id'      => 'required',
            'range'             => 'required',
            'days_to_register'  => 'required',
            'schedule'          => 'nullable',
        ]);

        $week = $getWeekData($validated['range'][0]);

        $incidence = EmployeeIncidences::create(array_merge([
            "employee_id"      => $validated['employee_id'],
            "incidence_id"     => $validated['incidence_id'],
            "validity_from"    => $validated['range'][0],
            "validity_to"      => $validated['range'][1],
            "days"             => $validated['days_to_register'],
            "schedule_id"      => $validated['schedule'],
            "branch_office_id" => $request->branch_office_id,
        ], $week));

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'relationship_id' => $incidence->id
        ]);

        return redirect()->route('/incidences-employee');
    }

    public function downloadDocument($id)
    {
        $incidence = EmployeeIncidences::findOrFail($id);

        if (!$incidence->file_path) {
            abort(404, 'Esta incidencia no tiene un documento adjunto.');
        }

        $disk = Storage::disk('remote_sftp');

        if (!$disk->exists($incidence->file_path)) {
            abort(404, 'El archivo no se encontró en el servidor remoto.');
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
        $allincidences = Incidence::select('id','name')->get();
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
                'week_year'   => $dt->format('Y'),
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

            $week = $getWeekData($validated['singleDate']);
            $hours = (int) $validated['txt_hours_to_register'];

            $base = [
                "employee_id"       => $validated['employee_id'],
                "incidence_id"      => $validated['incidence_id'],
                "validity_from"     => $validated['singleDate'],
                "validity_to"       => $validated['singleDate'],
                "branch_office_id"  => $request->branch_office_id,
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

            return redirect()->route('/incidences-employee');
        }

        if ($incidenceId === 3) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'range'             => 'required',
                'notes'             => 'nullable',
                'days_to_register'  => 'required',
            ]);

            $week = $getWeekData($validated['range'][0]);

            $oldData = $incidences_employee->getOriginal();

            $incidences_employee->update(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['range'][0],
                "validity_to"      => $validated['range'][1],
                "branch_office_id" => $request->branch_office_id,
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

            return redirect()->route('/incidences-employee');
        }

        if (in_array($incidenceId, [20, 19], true)) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'advance_date'      => 'required',
                'rest_date'         => 'required',
                'schedule'          => 'required',
                'branch_office_id'  => 'required',
                'notes'             => 'nullable',
            ]);

            $week = $getWeekData($validated['advance_date']);

            $oldData = $incidences_employee->getOriginal();

            $incidences_employee->update(array_merge([
                "employee_id"      => $validated['employee_id'],
                "incidence_id"     => $validated['incidence_id'],
                "validity_from"    => $validated['advance_date'],
                "validity_to"      => $validated['advance_date'],
                "before_date"      => $validated['advance_date'],
                "rest_date"        => $validated['rest_date'],
                "branch_office_id" => $request->branch_office_id,
                "comment"          => $validated['notes'],
                "schedule_id"      => $validated['schedule'],
                "days"             => null
            ], $week));

            Logs::create([
                'action' => 'UPDATE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_incidences',
                'date' => Carbon::now(),
                'old_data' => json_encode($oldData),
                'relationship_id' => $incidences_employee->id
            ]);

            return redirect()->route('/incidences-employee');
        }

        $documentIncidences = [53,10,8,22,56,5,4,7,6,49,29,14,15,13];

        if (in_array($incidenceId, $documentIncidences, true)) {
            $validated = $request->validate([
                'employee_id'       => 'required',
                'incidence_id'      => 'required',
                'document'          => 'required',
                'branch_office_id'  => 'required',
                'notes'             => 'nullable',
                'document_number'   => 'required',
                'range'             => 'required',
            ]);

            try {
                $disk = Storage::disk('remote_sftp');

                $dir = 'incidences/' . date('Y/m');
                $disk->makeDirectory($dir);

                $file = $request->file('document');
                $filename = uniqid('inc_', true).'.'.$file->getClientOriginalExtension();
                $remotePath = $dir.'/'.$filename;

                $disk->put($remotePath, file_get_contents($file->getRealPath()));

                $week = $getWeekData($validated['range'][0]);

                $oldData = $incidences_employee->getOriginal();

                $incidences_employee->update(array_merge([
                    "employee_id"       => $validated['employee_id'],
                    "incidence_id"      => $validated['incidence_id'],
                    "validity_from"     => $validated['range'][0],
                    "validity_to"       => $validated['range'][1],
                    "branch_office_id"  => $request->branch_office_id,
                    "comment"           => $validated['notes'],
                    "file_path"         => $remotePath,
                    "document_number"   => $validated['document_number'],
                    "days"              => null,
                ], $week));

                Logs::create([
                    'action' => 'UPDATE',
                    'user_id' => Auth::id(),
                    'table_name' => 'employee_incidences',
                    'date' => Carbon::now(),
                    'old_data' => json_encode($oldData),
                    'relationship_id' => $incidences_employee->id
                ]);

            } catch (\Throwable $e) {
                Log::error('SFTP failure', [
                    'message' => $e->getMessage(),
                    'root'    => config('filesystems.disks.remote_sftp.root'),
                    'host'    => config('filesystems.disks.remote_sftp.host'),
                ]);
                throw $e;
            }

            return redirect()->route('/incidences-employee');
        }

        $validated = $request->validate([
            'employee_id'       => 'required',
            'incidence_id'      => 'required',
            'range'             => 'required',
            'days_to_register'  => 'required',
            'schedule'          => 'nullable',
        ]);

        $oldData = $incidences_employee->getOriginal();

        $week = $getWeekData($validated['range'][0]);

        $incidences_employee->update(array_merge([
            "employee_id"      => $validated['employee_id'],
            "incidence_id"     => $validated['incidence_id'],
            "validity_from"    => $validated['range'][0],
            "validity_to"      => $validated['range'][1],
            "days"             => $validated['days_to_register'],
            "branch_office_id" => $request->branch_office_id,
            "schedule_id"      => $validated['schedule'],
        ], $week));

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'old_data' => json_encode($oldData),
            'relationship_id' => $incidences_employee->id
        ]);

        return redirect()->route('/incidences-employee');
    
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
        return redirect()->route('/incidences-employee');
    }

    public function approve(EmployeeIncidences $incidence)
    {
        $incidence->update([
            "approved_at" => now(),
            "approved_by" => Auth::user()->id,
        ]);

        if($incidence->incidence_id == 23){
            Txt::create([
                "employee_incidence_id" => $incidence->id,
                "date" => $incidence->validity_from,
                "employee_id" => $incidence->employee_id,
                "hours" => $incidence->hours_txt * -1,
                "approved_at" => now(),
                "approved_by" => Auth::user()->id,
                "week_number" => $incidence->week_number,
                "year" => $incidence->week_year,
                "branch_office_id" => $incidence->branch_office_id
            ]);
        }

        if($incidence->incidence_id == 3){
            $entryDate = Employee::findOrFail($incidence->employee_id)->entry_date;
            $seniority = Carbon::parse($entryDate)->diffInYears(Carbon::now());

            EmployeeDayVacation::create([
                "employee_id" => $incidence->employee_id,
                "amount" => $incidence->days * -1,
                "branch_office_id" => $incidence->branch_office_id,
                "employee_incidence_id" => $incidence->id,
                "date" => Carbon::now()->format('Y-m-d'),
                "seniority" => $seniority,
            ]);
        }

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'relationship_id' => $incidence->id
        ]);
        return redirect()->to('/employee/incidences-employee');
    }

    public function reject(EmployeeIncidences $incidence)
    {
        $incidence->update([
            "declined_at" => now(),
            "declined_by" => Auth::user()->id,
        ]);
        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_incidences',
            'date' => Carbon::now(),
            'relationship_id' => $incidence->id
        ]);
        return redirect()->to('/employee/incidences-employee');
    }

    public function rejectAll(Request $request)
    {
        $ids = $request->ids;
        $today = Carbon::now();
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
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        
        return redirect()->to('/employee/incidences-employee');
    }

    public function approveAll(Request $request)
    {
        $today = Carbon::now();
        $user = Auth::user();

        $incidences = EmployeeIncidences::whereIn('id', $request->ids)->get();

        foreach ($incidences as $incidence) {
            $incidence->update([
                "approved_at" => $today,
                "approved_by" => $user->id,
            ]);

            if ($incidence->incidence_id == 23) {
                Txt::create([
                    "employee_incidence_id" => $incidence->id,
                    "date" => $incidence->validity_from,
                    "employee_id" => $incidence->employee_id,
                    "hours" => $incidence->hours_txt * -1,
                    "approved_at" => $today,
                    "approved_by" => $user->id,
                    "week_number" => $incidence->week_number,
                    "year" => $incidence->week_year,
                    "branch_office_id" => $incidence->branch_office_id
                ]);
            }

            if ($incidence->incidence_id == 3) {
                $entryDate = Employee::findOrFail($incidence->employee_id)->entry_date;
                $seniority = Carbon::parse($entryDate)->diffInYears(Carbon::now());

                EmployeeDayVacation::create([
                    "employee_id" => $incidence->employee_id,
                    "amount" => $incidence->days * -1,
                    "branch_office_id" => $incidence->branch_office_id,
                    "employee_incidence_id" => $incidence->id,
                    "date" => $today->format('Y-m-d'),
                    "seniority" => $seniority,
                ]);
            }

            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now(),
                'relationship_id' => $incidence->id
            ]);
        }

        return redirect()->to('/employee/incidences-employee');
    }

    public function deleteAll(Request $request)
    {
        $ids = $request->ids;
        EmployeeIncidences::whereIn('id', $ids)->update([
            "deleted_by" => Auth::user()->id,
            "deleted_at" => Carbon::now()
        ]);

        $records = EmployeeIncidences::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'DECLINED',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }
        
        return redirect()->to('/employee/incidences-employee');
    }
}
