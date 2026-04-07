<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Models\Logs;
use App\Models\Motivo;
use App\Models\Schedules;
use App\Models\TxT;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeOvertimeController
{
    public function __construct(private EmployeeOvertime $employeeOvertime)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $overtimes = EmployeeOvertime::index( $request->empleado, $request->planta, $request->departamento, $request->estatus, $request->semana, $request->primaDominical);
        return $overtimes;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $view = 'overtimes';
        $motivos = Motivo::all();
        $schedules = Schedules::select('id', 'name', 'entry_time', 'leave_time')->get();
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $employees = Employee::query()->select('id','full_name', 'branch_office_id')->where('status', '!=', 'termination')->get();
        return Inertia::render('EmployeeOvertimes/Create', [
            'view' => $view,
            'motivos' => $motivos,
            'schedules' => $schedules,
            'branchOffices' => $branchOffices,
            'employees' => $employees
        ]);
    }

    public function getData(Request $request){
        $employeeData = EmployeeOvertime::search_employee_data($request->date, $request->employee_id);

        return $employeeData;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'employee_id'      => ['required', 'integer'],
            'date'             => ['required', 'date'],
            'motivo_id'        => ['required', 'integer'],
            'extemporaneous'   => ['sometimes', 'boolean'],
            'time_for_time'    => ['sometimes', 'boolean'],
            'full_shift'       => ['sometimes', 'boolean'],
            'external_pay'     => ['sometimes', 'boolean'],
            'double_overtime'  => ['nullable', 'numeric', 'min:0'],
            'triple_overtime'  => ['nullable', 'numeric', 'min:0'],
            'observations'     => ['nullable', 'string', 'max:500'],
            'double_shift'     => ['nullable', 'boolean'],
        ];

        $rules['moment'] = ['required', 'in:before,after,both'];
        $rules['hours']  = ['required', 'numeric', 'min:0.5'];

        $rules['shift_id'] = ['required_if:full_shift,true', 'nullable', 'integer'];

        $rules['pay_week_id'] = ['required_if:extemporaneous,true'];

        $rules['plant_id'] = [
            'exclude_if:time_for_time,true',
            'required_if:external_pay,true',
            'nullable',
            'integer'
        ];

        $messages = [
            // Mensajes generales
            'required'    => 'Este campo es obligatorio.',
            'integer'     => 'Debes ingresar un número entero válido.',
            'date'        => 'Debes seleccionar una fecha válida.',
            'boolean'     => 'La opción seleccionada no es válida.',
            'numeric'     => 'Debes ingresar un valor numérico.',
            'min'         => 'El valor ingresado no puede ser menor a :min.',
            'max'         => 'El texto ingresado supera el límite permitido de :max caracteres.',
            'in'          => 'La opción seleccionada no está permitida.',
            
            // Mensajes para reglas condicionales
            'required_if' => 'Este campo es obligatorio debido a las opciones seleccionadas anteriormente.',
        ];

        // Ejecución de la validación
        $validated = $request->validate($rules, $messages);

        $weekOvertime = null;
        $yearOvertime = null;
        $mondayDate = null;

        $date = Carbon::parse($validated['date']);
        $moment = $validated['moment'] ?? null;

        $entryTime = $request->input('shift_entry_time');
        $leaveTime = $request->input('shift_leave_time');

        $hasSundayPremium = false;

        if ($date->isSunday()) {
            $hasSundayPremium = true;
        } else {

            $entry = Carbon::parse($date->format('Y-m-d') . ' ' . $entryTime);
            $leave = Carbon::parse($date->format('Y-m-d') . ' ' . $leaveTime);

            if ($leave->lessThanOrEqualTo($entry)) {
                $leave->addDay();
            }

            $crossesToSunday = $entry->isSaturday() && $leave->isSunday();
            $momentIncludesAfter = in_array($moment, ['after', 'both'], true);

            if ($crossesToSunday && $momentIncludesAfter) {
                $hasSundayPremium = true;
            }
        }

        $overtimesJson = json_encode([
            'total' => (string) $validated['hours'],
            'double_overtime' => (string) $validated['double_overtime'],
            'triple_overtime' => (string) $validated['triple_overtime'],
            'sunday_premium' => $hasSundayPremium
        ]);

        $employee = Employee::find($validated['employee_id']);

        $newdate = Carbon::parse($validated['date']);

        $w = $newdate->isoWeek();
        $y = $newdate->isoWeekYear();


        if ($validated['extemporaneous']) {
            $payWeek = $validated['pay_week_id'];
            [$year, $weekPart] = explode('-W', $payWeek);

            $year = (int) $year;
            $week = (int) $weekPart;

            $weekOvertime = $week - 1;
            $yearOvertime = $year;

             if ($weekOvertime < 1) {
                $yearOvertime = $year - 1;
                $weekOvertime = (int) (new DateTime())->setDate($yearOvertime, 12, 28)->format('W');
            }

            $mondayDate = (new DateTime())->setISODate($yearOvertime, $weekOvertime, 1)->format('Y-m-d');

            EmployeeOvertime::create([
                'date' => $validated['date'],
                'comment' => $request['observations'],
                'employee_id' => $request['employee_id'],
                'week_number' => $weekOvertime,
                'week_year' => $yearOvertime,
                'branch_office_id' => $employee->branch_office_id,
                'overtimes' => "$overtimesJson",
                'schedule_id' => $request['shift_id'],
                'hours' => $request['hours'],
                'complete' => $request['full_shift'] ? 1 : 0,
                'type' => $request['moment'],
                'pay_external' => $request['external_pay'] ? 1 : 0,
                'external_branch_office_id' => $request['plant_id'],
                'untimely' => $validated['extemporaneous'] ? 1 : 0,
                'corrido' => $validated['double_shift'] ? 1 : 0,
                'untimely_date' => $mondayDate,
                'txt' => $validated['time_for_time'],
                'motivo' => $validated['motivo_id'],
                'has_error' => $request->has_error
            ]);

            return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);

        }

        $employeeOvertimes = EmployeeOvertime::create([
            'date' => $validated['date'],
            'comment' => $request['observations'],
            'employee_id' => $request['employee_id'],
            'week_number' => $w,
            'week_year' => $y,
            'branch_office_id' => $employee->branch_office_id,
            'overtimes' => "$overtimesJson",
            'schedule_id' => $request['shift_id'],
            'hours' => $request['hours'],
            'complete' => $request['full_shift'] ? 1 : 0,
            'type' => $request['moment'],
            'pay_external' => $request['external_pay'] ? 1 : 0,
            'external_branch_office_id' => $request['plant_id'],
            'untimely' => 0,
            'corrido' => $validated['double_shift'] ? 1 : 0,
            'untimely_date' => null,
            'txt' => $validated['time_for_time'],
            'motivo' => $validated['motivo_id'],
            'has_error' => $request->has_error
        ]);

        Logs::create([
            'action' => 'INSERT',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes',
            'date' => Carbon::now(),
            'relationship_id' => $employeeOvertimes->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeOvertime $employeeOvertime)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeOvertime $overtime)
    {
        $view = 'overtimes';
        $motivos = Motivo::all();
        $schedules = Schedules::select('id', 'name', 'entry_time', 'leave_time')->get();
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $employees = Employee::query()->select('id','full_name', 'branch_office_id')->where('status', '!=', 'termination')->get();
        return Inertia::render('EmployeeOvertimes/Edit', [
            'view' => $view,
            'motivos' => $motivos,
            'schedules' => $schedules,
            'branchOffices' => $branchOffices,
            'employees' => $employees,
            'overtime' => $overtime
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeOvertime $overtime)
    {
        $rules = [
            'employee_id'      => ['required', 'integer'],
            'date'             => ['required', 'date'],
            'motivo_id'        => ['required', 'integer'],
            'extemporaneous'   => ['sometimes', 'boolean'],
            'time_for_time'    => ['sometimes', 'boolean'],
            'full_shift'       => ['sometimes', 'boolean'],
            'external_pay'     => ['sometimes', 'boolean'],
            'double_overtime'  => ['nullable', 'numeric', 'min:0'],
            'triple_overtime'  => ['nullable', 'numeric', 'min:0'],
            'observations'     => ['nullable', 'string', 'max:500'],
            'double_shift'     => ['nullable', 'boolean'],
        ];

        $rules['moment'] = ['required', 'in:before,after,both'];
        $rules['hours']  = ['required', 'numeric', 'min:0.5'];

        $rules['shift_id'] = ['required_if:full_shift,true', 'nullable', 'integer'];

        $rules['pay_week_id'] = ['required_if:extemporaneous,true'];

        $rules['plant_id'] = [
            'exclude_if:time_for_time,true',
            'required_if:external_pay,true',
            'nullable',
            'integer'
        ];

        $messages = [
            // Mensajes generales
            'required'    => 'Este campo es obligatorio.',
            'integer'     => 'Debes ingresar un número entero válido.',
            'date'        => 'Debes seleccionar una fecha válida.',
            'boolean'     => 'La opción seleccionada no es válida.',
            'numeric'     => 'Debes ingresar un valor numérico.',
            'min'         => 'El valor ingresado no puede ser menor a :min.',
            'max'         => 'El texto ingresado supera el límite permitido de :max caracteres.',
            'in'          => 'La opción seleccionada no está permitida.',
            
            // Mensajes para reglas condicionales
            'required_if' => 'Este campo es obligatorio debido a las opciones seleccionadas anteriormente.',
        ];

        // Ejecución de la validación
        $validated = $request->validate($rules, $messages);

        $weekOvertime = null;
        $yearOvertime = null;
        $mondayDate = null;

        $date = Carbon::parse($validated['date']);
        $moment = $validated['moment'] ?? null;

        $entryTime = $request->input('shift_entry_time');
        $leaveTime = $request->input('shift_leave_time');

        $hasSundayPremium = false;

        if ($date->isSunday()) {
            $hasSundayPremium = true;
        } else {

            $entry = Carbon::parse($date->format('Y-m-d') . ' ' . $entryTime);
            $leave = Carbon::parse($date->format('Y-m-d') . ' ' . $leaveTime);

            if ($leave->lessThanOrEqualTo($entry)) {
                $leave->addDay();
            }

            $crossesToSunday = $entry->isSaturday() && $leave->isSunday();
            $momentIncludesAfter = in_array($moment, ['after', 'both'], true);

            if ($crossesToSunday && $momentIncludesAfter) {
                $hasSundayPremium = true;
            }
        }

        $overtimesJson = json_encode([
            'total' => (string) $validated['hours'],
            'double_overtime' => (string) $validated['double_overtime'],
            'triple_overtime' => (string) $validated['triple_overtime'],
            'sunday_premium' => $hasSundayPremium
        ]);

        $employee = Employee::find($validated['employee_id']);

        $newdate = Carbon::parse($validated['date']);

        $w = $newdate->isoWeek();
        $y = $newdate->isoWeekYear();


        if ($validated['extemporaneous']) {
            $payWeek = $validated['pay_week_id'];
            [$year, $weekPart] = explode('-W', $payWeek);

            $year = (int) $year;
            $week = (int) $weekPart;

            $weekOvertime = $week - 1;
            $yearOvertime = $year;

             if ($weekOvertime < 1) {
                $yearOvertime = $year - 1;
                $weekOvertime = (int) (new DateTime())->setDate($yearOvertime, 12, 28)->format('W');
            }

            $mondayDate = (new DateTime())->setISODate($yearOvertime, $weekOvertime, 1)->format('Y-m-d');

            $overtime->update([
                'date' => $validated['date'],
                'comment' => $request['observations'],
                'employee_id' => $request['employee_id'],
                'week_number' => $weekOvertime,
                'week_year' => $yearOvertime,
                'branch_office_id' => $employee->branch_office_id,
                'overtimes' => "$overtimesJson",
                'schedule_id' => $request['shift_id'],
                'hours' => $request['hours'],
                'complete' => $request['full_shift'] ? 1 : 0,
                'type' => $request['moment'],
                'pay_external' => $request['external_pay'] ? 1 : 0,
                'external_branch_office_id' => $request['plant_id'],
                'untimely' => $validated['extemporaneous'] ? 1 : 0,
                'corrido' => $validated['double_shift'] ? 1 : 0,
                'untimely_date' => $mondayDate,
                'txt' => $validated['time_for_time'],
                'motivo' => $validated['motivo_id'],
                'has_error' => $request->has_error
            ]);

            return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);

        }

        $oldData = $overtime->getOriginal();

        $overtime->update([
            'date' => $validated['date'],
            'comment' => $request['observations'],
            'employee_id' => $request['employee_id'],
            'week_number' => $w,
            'week_year' => $y,
            'branch_office_id' => $employee->branch_office_id,
            'overtimes' => "$overtimesJson",
            'schedule_id' => $request['shift_id'],
            'hours' => $request['hours'],
            'complete' => $request['full_shift'] ? 1 : 0,
            'type' => $request['moment'],
            'pay_external' => $request['external_pay'] ? 1 : 0,
            'external_branch_office_id' => $request['plant_id'],
            'untimely' => 0,
            'corrido' => $validated['double_shift'] ? 1 : 0,
            'untimely_date' => null,
            'txt' => $validated['time_for_time'],
            'motivo' => $validated['motivo_id'],
            'has_error' => $request->has_error
        ]);

        Logs::create([
            'action' => 'UPDATE',
            'user_id' => Auth::id(),
            'old_data' => json_encode($oldData),
            'table_name' => 'employee_overtimes',
            'date' => Carbon::now(),
            'relationship_id' => $overtime->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeOvertime $overtime)
    {
        $overtime->delete();

        Logs::create([
            'action' => 'DELETE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes',
            'date' => Carbon::now(),
            'relationship_id' => $overtime->id
        ]);
        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    public function approve(Request $request)
    {
        $employee_overtime = EmployeeOvertime::find($request->id);
        $employee_overtime->approved_at = Carbon::now();
        $employee_overtime->approved_by = Auth::user()->id;
        $employee_overtime->declined_at = null;
        $employee_overtime->declined_by = null;
        $employee_overtime->save();

        $schedule = Schedules::find($employee_overtime->schedule_id);

        if($employee_overtime->txt == 1){
            TxT::create([
                'date' => $employee_overtime->date,
                'employee_id' => $employee_overtime->employee_id,
                'week_number' => $employee_overtime->week_number,
                'week_year' => $employee_overtime->week_year,
                'branch_office_id' => $employee_overtime->branch_office_id,
                'schedule_id' => $schedule->id ?? null,
                'schedule_entry_time' => $schedule->entry_time ?? null,
                'schedule_leave_time' => $schedule->leave_time ?? null,
                'hours' => $employee_overtime->hours,
                'moment' => $employee_overtime->moment,
                'approved_at' => Carbon::now(),
                'approved_by' => Auth::id()
            ]);
        }

        Logs::create([
            'action' => 'APPROVE',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes',
            'date' => Carbon::now(),
            'relationship_id' => $employee_overtime->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    public function decline(Request $request)
    {
        $employee_overtime = EmployeeOvertime::find($request->id);
        $employee_overtime->declined_at = Carbon::now();
        $employee_overtime->declined_by = Auth::user()->id;
        $employee_overtime->approved_at = null;
        $employee_overtime->approved_by = null;
        $employee_overtime->save();

        Logs::create([
            'action' => 'DECLINED',
            'user_id' => Auth::id(),
            'table_name' => 'employee_overtimes',
            'date' => Carbon::now(),
            'relationship_id' => $employee_overtime->id
        ]);

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    public function multiApprove(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertime::whereIn('id', $ids)->update([
            'approved_at' => Carbon::now(),
            'approved_by' => Auth::user()->id,
            'declined_at' => null,
            'declined_by' => null
        ]);

        $records = EmployeeOvertime::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    public function multiDecline(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertime::whereIn('id', $ids)->update([
            'declined_at' => Carbon::now(),
            'declined_by' => Auth::user()->id,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $records = EmployeeOvertime::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->ids;
        EmployeeOvertime::whereIn('id', $ids)->delete();

        $records = EmployeeOvertime::whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Logs::create([
                'action' => 'APPROVE',
                'user_id' => Auth::id(),
                'table_name' => 'employee_overtimes',
                'date' => Carbon::now(),
                'relationship_id' => $record->id
            ]);
        }

        return redirect()->route('employee-overtimes.index')->with(['page' => "1"]);
    }
}
