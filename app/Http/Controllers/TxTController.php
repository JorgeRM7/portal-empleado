<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Logs;
use App\Models\TxT;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TxTController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $departments = Departments::all();
        $user = Auth::user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
            // dd($branchOffices);
        return Inertia::render('TXT/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'branchOffices' => $branchOffices
        ]);
    }

    public function filterData(Request $request)
    {

    // dd($request);
        $txts = TxT::index_data(
            $request->branch_office_id,
            $request->employee_id,
            $request->department_id,
            $request->status,
            $request->week,
            $request->year,
            $request->eliminated
        );
        return $txts;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TXT/Create');
    }

    public function searchEmployeeData(Request $request)
    {
        $txtData = TxT::search_employee_data($request->date, $request->employee_id);
        return $txtData;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'moment' => 'required',
            'hours' => 'required'
        ], [
            'moment.required' => 'El momento es requerido',
            'hours.required' => 'Las horas son requeridas'
        ]);

        $date = new DateTime($request->date);
        $week = $date->format('W');
        $year = $date->format('Y');

        $txt = TxT::create([
            'moment' => $request->moment,
            'hours' => $request->hours,
            'comment' => $request->observations,
            'branch_office_id' => $request->branchOfficeId,
            'employee_id' => $request->employeeId,
            'date' => $request->date,
            'schedule_entry_time' => $request->schedule_entry,
            'schedule_leave_time' => $request->schedule_exit,
            'schedule_id' => $request->scheduleId,
            'week_number' => $week,
            'week_year' => $year,
        ]);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');

    }

    public function validateTxT(Request $request)
    {
        $txt = TxT::find($request->id);
        $txt->validated_at = now();
        $txt->save();

        Logs::create([
            'action' => 'VALIDATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');
    }

    public function approveTXT(Request $request)
    {
        $txt = TxT::find($request->id);
        $txt->approved_at = Carbon::now();
        $txt->approved_by = Auth::user()->id;
        $txt->save();

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');
    }

    public function declineTXT(Request $request)
    {
        $txt = TxT::find($request->id);
        $txt->declined_at = Carbon::now();
        $txt->declined_by = Auth::user()->id;
        $txt->save();

        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');
    }

    /**
     * Display the specified resource.
     */
    public function show(TxT $txT)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TxT $txt)
    {
        return Inertia::render('TXT/Edit', [
            'txt' => $txt,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TxT $txt)
    {
        $validated = $request->validate([
            'moment' => 'required',
            'hours' => 'required'
        ], [
            'moment.required' => 'El momento es requerido',
            'hours.required' => 'Las horas son requeridas'
        ]);

        $oldData = $txt->getOriginal();

        $date = new DateTime($request->date);
        $week = $date->format('W');
        $year = $date->format('Y');

        $txt->update([
            'moment' => $request->moment,
            'hours' => $request->hours,
            'comment' => $request->observations,
            'branch_office_id' => $request->branchOfficeId,
            'employee_id' => $request->employeeId,
            'date' => $request->date,
            'schedule_entry_time' => $request->schedule_entry,
            'schedule_leave_time' => $request->schedule_exit,
            'schedule_id' => $request->scheduleId,
            'week_number' => $week,
            'week_year' => $year,
        ]);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old_data' => json_encode($oldData),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TxT $txt)
    {
        $txt->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt');
    }

    public function uploadDocument(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $txt = TxT::findOrFail($request->txt_id);

        try{
            $disk = Storage::disk('remote_sftp');

            $dir = 'txt/' . date('Y/m');
            $disk->makeDirectory($dir);

            $file = $request->file('document');
            $filename = uniqid('txt_', true).'.'.$file->getClientOriginalExtension();
            $remotePath = $dir.'/'.$filename;

            $disk->put($remotePath, file_get_contents($file->getRealPath()));

            $txt->file_path = $remotePath;
            $txt->save();

        }catch (\Throwable $e) {
                Log::error('SFTP failure', [
                    'message' => $e->getMessage(),
                    'root'    => config('filesystems.disks.remote_sftp.root'),
                    'host'    => config('filesystems.disks.remote_sftp.host'),
                ]);
                throw $e;
            }

        return redirect()->route('txt');
    }

    public function downloadDocument($id)
    {
        $txt = TxT::findOrFail($id);

        if (!$txt->file_path) {
            abort(404, 'Esta incidencia no tiene un documento adjunto.');
        }

        $disk = Storage::disk('remote_sftp');

        if (!$disk->exists($txt->file_path)) {
            abort(404, 'El archivo no se encontró en el servidor remoto.');
        }
        return $disk->response($txt->file_path);
    }

    public function indexHistory()
{
        $employees = Employee::query()->where('status', '!=', 'termination')->get();
        $departments = Departments::all();

        // CAMBIO: En lugar de BranchOffice::all(), usa la relación del usuario
        $user = auth()->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('TXTHistory/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'branchOffices' => $branchOffices, // Ahora solo enviará las 3 permitidas
        ]);
    }

    // public function indexHistory()
    // {
    //     $employees = Employee::query()->where('status', '!=', 'termination')->get();
    //     $departments = Departments::all();
    //     $branchOffices = BranchOffice::all();
    //     return Inertia::render('TXTHistory/Index', [
    //         'employees' => $employees,
    //         'departments' => $departments,
    //         'branchOffices' => $branchOffices,
    //     ]);
    // }

    public function getTxtHistory(Request $request)
    {
        $txts = TxT::index_data_history($request->branch_office_id, $request->employee_id, $request->department_id, $request->week, $request->year, $request->eliminated);
        return $txts;
    }

    public function getTxtHistoryExcel(Request $request)
    {
        $txts = TxT::excelHistory($request->branch_office_id);
        return $txts;
    }

    public function editHistory($id)
    {
        $txt = TxT::find($id);
        return Inertia::render('TXTHistory/Edit', [
            'txt' => $txt,
        ]);
    }

    public function createHistory()
    {
        return Inertia::render('TXTHistory/Create');
    }

    public function storeHistory(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required',
            'date' => 'required',
            'hours' => 'required',
        ], [
            'employee_id.required' => 'El empleado es requerido',
            'date.required' => 'La fecha es requerida',
            'hours.required' => 'Las horas son requeridas',
        ]);

        

        $txt = TxT::create([
            'employee_id' => $request->employee_id['id'],
            'date' => $request->date,
            'hours' => $request->hours,
            'approved_at' => Carbon::now(),
            'approved_by' => Auth::id(),
            'week_number' => Carbon::parse($request->date)->weekOfYear,
            'week_year' => Carbon::parse($request->date)->year,
            'branch_office_id' => $request->branch_office_id,
        ]);

        Logs::create([
            'action' => 'CREATE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);

        return redirect()->route('txt-history.index');
    }

    public function updateHistory(Request $request, $id)
    {
        $validated = $request->validate([
            'hours' => 'required',
        ], [
            'hours.required' => 'Las horas son requeridas',
        ]);

        $txt = TxT::find($id);
        $oldData = $txt->getOriginal();
        $txt->update([
            'hours' => $validated['hours'],
            'employee_id' => $request->employee_id['id'],
            'date' => $request->date,
        ]);
        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old_data' => json_encode($oldData),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);
        return redirect()->route('txt-history.index');
    }

    public function deleteHistory($id)
    {
        $txt = TxT::find($id);
        $txt->delete();
        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_time_by_time',
            'date' => Carbon::now(),
            'relationship_id' => $txt->id
        ]);
        return redirect()->route('txt-history.index');
    }
}
