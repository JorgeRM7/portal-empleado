<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\EmployeeSalaryAdjustment;
use App\Models\Departments;
use App\Models\Position;
use App\Models\Employee;
use App\Models\PaymentData;
use App\Models\TypeSalaryAdjustmentMovement;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class EmployeeSalaryAdjustmentsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plantas = Auth::user()
            ->branchOffices()
            ->select('branch_offices.id', 'branch_offices.code', 'branch_offices.name')
            ->get();
        $departamentos = Departments::select("id", "name")->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->whereNotIn('status', ['termination'])
            ->get();

        return Inertia::render('EmployeeSalaryAdjustments/Index', [
            'Plantas' => $plantas,
            'Departamentos' => $departamentos,
            'Empleados' => $employees,
        ]);
    }

    public function filterData(Request $request)
    {
        $startOfWeek = null;
        $endOfWeek   = null;

        if ($request->week) {

            [$year, $weekNumber] = explode('-W', $request->week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $year, (int) $weekNumber)
                ->startOfWeek(Carbon::MONDAY)
                ->startOfDay();

            $endOfWeek = $startOfWeek->copy()
                ->endOfWeek(Carbon::SUNDAY)
                ->endOfDay();
        }

        $rows = EmployeeSalaryAdjustment::indexAjustes(
            $request->branch_office_id,
            $startOfWeek,
            $endOfWeek,
            $request->department_id,
            $request->status,
            $request->employee_id
        );

        // Transformación
        $rows = collect($rows)->map(function ($row) {

            // Estado ajuste
            if ($row->nombre_aprobo && $row->approved_at) {
                $row->adjust_state = 'approved';
                $row->name_validator = $row->nombre_aprobo;
            }
            elseif ($row->desist_at) {
                $row->adjust_state = 'desisted';
                $row->name_validator = $row->nombre_declino;
            }
            else {
                $row->adjust_state = 'in_process';
                $row->name_validator = $row->nombre_reaplico;
            }

            return $row;

        });

        return $rows;
    }

    public function filterDataWeekly(Request $request)
    {
        $startOfWeek = null;
        $endOfWeek   = null;

        if ($request->week) {

            [$year, $weekNumber] = explode('-W', $request->week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $year, (int) $weekNumber)
                ->startOfWeek(Carbon::MONDAY)
                ->startOfDay();

            $endOfWeek = $startOfWeek->copy()
                ->endOfWeek(Carbon::SUNDAY)
                ->endOfDay();
        }

        $rows = EmployeeSalaryAdjustment::indexWeekly(
            $request->branch_office_id,
            $startOfWeek,
            $endOfWeek,
            $request->department_id,
            $request->status,
            $request->employee_id
        );

        $rows = collect($rows)->map(function ($row) {

            $statusMap = [
                1 => ['label' => 'NUEVO INGRESO', 'severity' => 'info'],
                2 => ['label' => 'PROMOCIÓN', 'severity' => 'success'],
                3 => ['label' => 'CAMBIO DE PUESTO', 'severity' => 'info'],
                4 => ['label' => 'PRÓRROGA', 'severity' => 'secondary'],
                5 => ['label' => 'DESISTIDO', 'severity' => 'warning'],
                6 => ['label' => 'RECHAZADO', 'severity' => 'danger'],
            ];

            $status = $statusMap[$row->status] ?? [
                'label' => 'SIN ESTADO',
                'severity' => 'contrast'
            ];

            $row->status_label = $status['label'];
            $row->status_severity = $status['severity'];

            return $row;
        });

        return $rows;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departamentos = Departments::select("id", "name")->get();
        $positions = Position::select("id", "name")->get();
        $tiposAjuste = TypeSalaryAdjustmentMovement::select("id", "name")
            ->where('id', '!=', 2)
            ->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id', 'position_id', 'department_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        

        return Inertia::render('EmployeeSalaryAdjustments/Create', [
            'Departamentos' => $departamentos,
            'Puestos' => $positions,
            'Empleados' => $employees,
            'TiposAjuste' => $tiposAjuste,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate(
            [
                'employee_id' => ['required'],
                'type_salary_adjustment_movement_id' => ['required', 'numeric'],
                'days_period' => ['required', 'numeric'],
                'actual_position_id' => ['nullable'],
                'actual_department_id' => ['nullable'],
                'start_training' => ['required', 'date'],
                'new_position_id' => ['required', 'numeric'],
                'new_department_id' => ['required', 'numeric'],
                'base_ajuste' => ['nullable'],
                'comment' => ['nullable', 'string', 'max:1500'],

            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'employee_id' => 'Empleado',
                'type_salary_adjustment_movement_id' => 'Tipo de ajuste',
                'days_period' => 'Días de periodo',
                'actual_position_id' => 'Puesto actual',
                'actual_department_id' => 'Departamento actual',
                'start_training' => 'Fecha de inicio de capacitación',
                'new_position_id' => 'Nuevo puesto',
                'new_department_id' => 'Nuevo departamento',
                'comment' => 'Observaciones',
            ]
        );

        try {

            // Si employee viene como objeto
            $employeeId = is_array($validated['employee_id'])
                ? $validated['employee_id']['id']
                : $validated['employee_id'];

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Obtener empleado
            |--------------------------------------------------------------------------
            */
            $employee = Employee::find($employeeId);

            if (!$employee) {
                return back()->with('error','No se encontró el empleado.');
            }

            $branch_office_id = $employee->branch_office_id;

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Obtener salario actual
            |--------------------------------------------------------------------------
            */
            $payment = PaymentData::where('owner_id',$employeeId)->first();

            if (!$payment) {
                return back()->with('error','No se encontró el salario del empleado.');
            }

            $prev_salary = $payment->daily_salary;

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Obtener salario nueva posición
            |--------------------------------------------------------------------------
            */
            $position = Position::find($validated['new_position_id']);

            if (!$position) {
                return back()->with('error','No se encontró la nueva posición.');
            }

            if ($validated['base_ajuste'] == 'prueba') {
                $adjust_salary = $position->daily_salary_in_trial;
                $base_ajuste_v = 1;
            } else {
                $adjust_salary = $position->daily_salary;
                $base_ajuste_v = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Fechas
            |--------------------------------------------------------------------------
            */
            $start = Carbon::parse($validated['start_training']);

            $days = (int) $validated['days_period'];

            $end_training = $start->copy()->addDays($days);

            $week_number = $end_training->weekOfYear;
            $week_year = $end_training->isoWeekYear;

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ Status
            |--------------------------------------------------------------------------
            */
            $status = $validated['type_salary_adjustment_movement_id'] == 1 ? 2 : 3;

            /*
            |--------------------------------------------------------------------------
            | 6️⃣ Crear registro
            |--------------------------------------------------------------------------
            */
            EmployeeSalaryAdjustment::create([
                'employee_id' => $employeeId,
                'branch_office_id' => $branch_office_id,
                'type_salary_adjustment_movement_id' => $validated['type_salary_adjustment_movement_id'],
                'actual_department_id' => $validated['actual_department_id'],
                'actual_position_id' => $validated['actual_position_id'],
                'new_department_id' => $validated['new_department_id'],
                'new_position_id' => $validated['new_position_id'],
                'start_training' => $start,
                'end_training' => $end_training,
                'comment' => $validated['comment'] ?? null,
                'days_period' => $validated['days_period'],
                'prev_salary' => $prev_salary,
                'adjust_salary' => $adjust_salary,
                'date' => now(),
                'week_number' => $week_number,
                'week_year' => $week_year,
                'status' => $status,
                'adjust_base_trial' => $base_ajuste_v,
            ]);

            return redirect()
                ->route('employee-salary-adjustments.index')
                ->with('success','Registro creado correctamente.');


        } catch (\Throwable $e) {

            Log::error('Error al crear el registro', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear el registro.');
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departamentos = Departments::select("id", "name")->get();
        $positions = Position::select("id", "name")->get();
        $tiposAjuste = TypeSalaryAdjustmentMovement::select("id", "name")
            ->where('id', '!=', 2)
            ->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id', 'position_id', 'department_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        

        return Inertia::render('EmployeeSalaryAdjustments/Show', [
            'Ajuste' => EmployeeSalaryAdjustment::findOrFail($id),
            'Departamentos' => $departamentos,
            'Puestos' => $positions,
            'Empleados' => $employees,
            'TiposAjuste' => $tiposAjuste,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $departamentos = Departments::select("id", "name")->get();
        $positions = Position::select("id", "name")->get();
        $tiposAjuste = TypeSalaryAdjustmentMovement::select("id", "name")
            ->where('id', '!=', 2)
            ->get();
        $employees = Employee::select('id', 'full_name', 'branch_office_id', 'position_id', 'department_id')
            ->whereNotIn('status', ['termination'])
            ->get();
        

        return Inertia::render('EmployeeSalaryAdjustments/Edit', [
            'Ajuste' => EmployeeSalaryAdjustment::findOrFail($id),
            'Departamentos' => $departamentos,
            'Puestos' => $positions,
            'Empleados' => $employees,
            'TiposAjuste' => $tiposAjuste,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeSalaryAdjustment $employeeSalaryAdjustment)
    {
        $validated = $request->validate(
            [
                'employee_id' => ['required'],
                'type_salary_adjustment_movement_id' => ['required', 'numeric'],
                'days_period' => ['required', 'numeric'],
                'actual_position_id' => ['nullable'],
                'actual_department_id' => ['nullable'],
                'start_training' => ['required', 'date'],
                'new_position_id' => ['required', 'numeric'],
                'new_department_id' => ['required', 'numeric'],
                'base_ajuste' => ['nullable'],
                'comment' => ['nullable', 'string', 'max:1500'],

            ],
            // ===== MENSAJES GENERALES =====
            [
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'employee_id' => 'Empleado',
                'type_salary_adjustment_movement_id' => 'Tipo de ajuste',
                'days_period' => 'Días de periodo',
                'actual_position_id' => 'Puesto actual',
                'actual_department_id' => 'Departamento actual',
                'start_training' => 'Fecha de inicio de capacitación',
                'new_position_id' => 'Nuevo puesto',
                'new_department_id' => 'Nuevo departamento',
                'comment' => 'Observaciones',
            ]
        );

        try {

            // Si employee viene como objeto
            $employeeId = is_array($validated['employee_id'])
                ? $validated['employee_id']['id']
                : $validated['employee_id'];

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Obtener empleado
            |--------------------------------------------------------------------------
            */
            $employee = Employee::find($employeeId);

            if (!$employee) {
                return back()->with('error','No se encontró el empleado.');
            }

            $branch_office_id = $employee->branch_office_id;

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Obtener salario actual
            |--------------------------------------------------------------------------
            */
            $payment = PaymentData::where('owner_id',$employeeId)->first();

            if (!$payment) {
                return back()->with('error','No se encontró el salario del empleado.');
            }

            $prev_salary = $payment->daily_salary;

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Obtener salario nueva posición
            |--------------------------------------------------------------------------
            */
            $position = Position::find($validated['new_position_id']);

            if (!$position) {
                return back()->with('error','No se encontró la nueva posición.');
            }

            if ($validated['base_ajuste'] == 'prueba') {
                $adjust_salary = $position->daily_salary_in_trial;
                $base_ajuste_v = 1;
            } else {
                $adjust_salary = $position->daily_salary;
                $base_ajuste_v = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Fechas
            |--------------------------------------------------------------------------
            */
            $start = Carbon::parse($validated['start_training']);

            $days = (int) $validated['days_period'];

            $end_training = $start->copy()->addDays($days);

            $week_number = $end_training->weekOfYear;
            $week_year = $end_training->isoWeekYear;

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ Status
            |--------------------------------------------------------------------------
            */
            $status = $validated['type_salary_adjustment_movement_id'] == 1 ? 2 : 3;

            /*
            |--------------------------------------------------------------------------
            | 6️⃣ Crear registro
            |--------------------------------------------------------------------------
            */
            $employeeSalaryAdjustment->update([
                'employee_id' => $employeeId,
                'branch_office_id' => $branch_office_id,
                'type_salary_adjustment_movement_id' => $validated['type_salary_adjustment_movement_id'],
                'actual_department_id' => $validated['actual_department_id'],
                'actual_position_id' => $validated['actual_position_id'],
                'new_department_id' => $validated['new_department_id'],
                'new_position_id' => $validated['new_position_id'],
                'start_training' => $start,
                'end_training' => $end_training,
                'comment' => $validated['comment'] ?? null,
                'days_period' => $validated['days_period'],
                'prev_salary' => $prev_salary,
                'adjust_salary' => $adjust_salary,
                'date' => now(),
                'week_number' => $week_number,
                'week_year' => $week_year,
                'status' => $status,
                'adjust_base_trial' => $base_ajuste_v,
            ]);

            return redirect()
                ->route('employee-salary-adjustments.index')
                ->with('success','Registro editado correctamente.');


        } catch (\Throwable $e) {

            Log::error('Error al editar el registro', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error editar el registro.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeSalaryAdjustment $employeeSalaryAdjustment)
    {
        $employeeSalaryAdjustment->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
    
    public function validateSalary(Request $request)
    {

        $request->validate(
            [
                'id' => 'required|integer|exists:employee_salary_adjustments,id',
                'acuerdo' => 'required|boolean',

                // Solo si NO hay acuerdo
                'salarioDiarioNuevo' => 'nullable|required_if:acuerdo,false|numeric|min:0',
                'totalPercepciones' => 'nullable|required_if:acuerdo,false|numeric|min:0',
                'compensacionNuevo' => 'nullable|required_if:acuerdo,false|numeric|min:0',
                'netoSemanalNuevo' => 'nullable|required_if:acuerdo,false|numeric|min:0',
                'observaciones' => 'nullable|string|max:1000',

            ],
            // ===== MENSAJES GENERALES =====
            [
                'required_if' => 'El campo :attribute es obligatorio.',
                'required' => 'El campo :attribute es obligatorio.',
                'string'   => 'El campo :attribute debe ser texto.',
                'integer'  => 'El campo :attribute debe ser un número entero.',
                'numeric'  => 'El campo :attribute debe ser un número.',
                'min'      => 'El campo :attribute debe ser igual o mayor a :min.',
                'max'      => 'El campo :attribute no debe exceder :max caracteres.',
                'unique' => 'El campo :attribute ya está registrado.',
            ],
            // ===== ATRIBUTOS =====
            [
                'name' => 'Nombre',
                'id' => 'Registro',
                'acuerdo' => 'Acuerdo de sueldo',
                'salarioDiarioNuevo' => 'Salario diario',
                'totalPercepciones' => 'Total percepciones',
                'compensacionNuevo' => 'Compensación',
                'netoSemanalNuevo' => 'Neto semanal',
                'observaciones' => 'Observaciones',
            ]
        );

        $ajuste = EmployeeSalaryAdjustment::findOrFail($request->id);

        $hoy = now();

        if ($request->acuerdo) {
            $ajuste->update([
                'adjust_salary_confirmation' => $hoy
            ]);
        } else {
            $ajuste->update([
                'approved_daily_sal' => $request->salarioDiarioNuevo,
                'approved_net_week' => $request->totalPercepciones,
                'approved_comp' => $request->compensacionNuevo,
                'adjust_salary_approved' => $request->netoSemanalNuevo,
                'salary_approved_comment' => $request->observaciones,
                'adjust_salary_confirmation' => $hoy
            ]);
        }

        return redirect()->back()->with('success', 'Registro validado correctamente');
    }

    // Formato de firmas
    public function getFormat($id)
    {
        // ================== QUERY
        $datos = DB::selectOne("
            SELECT 
                esa.*,
                e.full_name AS employee_name,
                d.name AS actual_department_name,
                p.name AS actual_position_name,
                d2.name AS new_department_name,
                p2.name AS new_position_name,
                bo.name AS planta,
                tsam.name AS tipo_ajuste,
                u.name AS approved_by_name,
                pd.meta AS json_semanal_actual,
                p.perceptions_adjust AS s_diario_neto,
                pd.daily_salary AS s_diario,
                p.compensations_adjust AS comp_actual,

                CASE 
                    WHEN esa.adjust_base_trial = 1 THEN p2.daily_salary_in_trial
                    ELSE p2.daily_salary
                END AS sueldo_nuevo,

                CASE 
                    WHEN esa.adjust_base_trial = 1 THEN p2.perceptions_in_trial
                    ELSE p2.perceptions_adjust
                END AS s_diario_nuevo_neto,

                CASE 
                    WHEN esa.adjust_base_trial = 1 THEN p2.net_in_trial
                    ELSE p2.net_in_adjust
                END AS neto_nuevo,

                CASE 
                    WHEN esa.adjust_base_trial = 1 THEN p2.compensation_in_trial
                    ELSE p2.compensation
                END AS comp_nuevo

            FROM employee_salary_adjustments esa
            LEFT JOIN employees e ON esa.employee_id = e.id
            LEFT JOIN departments d2 ON esa.new_department_id = d2.id
            LEFT JOIN positions p2 ON esa.new_position_id = p2.id
            LEFT JOIN departments d ON esa.actual_department_id = d.id
            LEFT JOIN positions p ON esa.actual_position_id = p.id
            LEFT JOIN users u ON esa.approved_by = u.id
            LEFT JOIN branch_offices bo ON esa.branch_office_id = bo.id
            LEFT JOIN type_salary_adjustment_movements tsam 
                ON tsam.id = esa.type_salary_adjustment_movement_id
            LEFT JOIN payment_data pd 
                ON pd.owner_id = esa.employee_id

            WHERE esa.deleted_at IS NULL
            AND esa.id = ?
        ", [$id]);

        if (!$datos) {
            abort(404);
        }

        // ================== NETO SEMANAL ACTUAL (JSON)
        $neto_sem_actual = 0;

        if (!empty($datos->json_semanal_actual)) {
            $json = json_decode($datos->json_semanal_actual, true);

            $salary = $json['weekly_salary'] ?? null;

            if ($salary !== null && $salary !== '') {
                $salary = preg_replace('/[^0-9.]/', '', $salary);

                if (is_numeric($salary)) {
                    $neto_sem_actual = (float) $salary;
                }
            }
        }

        // ================== VALIDAR SI YA FUE APROBADO
        if (empty($datos->adjust_salary_approved)) {
            $sueldo_nuevo = $datos->sueldo_nuevo;
            $neto_nuevo = $datos->s_diario_nuevo_neto;
            $comp_nuevo = $datos->comp_nuevo;
            $semanal_nuevo = $datos->neto_nuevo;
        } else {
            $sueldo_nuevo = $datos->approved_daily_sal;
            $neto_nuevo = $datos->approved_net_week;
            $comp_nuevo = $datos->approved_comp;
            $semanal_nuevo = $datos->adjust_salary_approved;
        }

        // ================== CAST A FLOAT
        $s_diario = (float) ($datos->s_diario ?? 0);
        $s_diario_neto = (float) ($datos->s_diario_neto ?? 0);
        $comp_actual = (float) ($datos->comp_actual ?? 0);

        $sueldo_nuevo = (float) ($sueldo_nuevo ?? 0);
        $neto_nuevo = (float) ($neto_nuevo ?? 0);
        $comp_nuevo = (float) ($comp_nuevo ?? 0);
        $semanal_nuevo = (float) ($semanal_nuevo ?? 0);

        // ================== DIFERENCIAS
        $sueldo_dif = $sueldo_nuevo - $s_diario;
        $semanal_dif = $semanal_nuevo - $neto_sem_actual;
        $comp_dif = $comp_nuevo - $comp_actual;
        $neto_dif = $neto_nuevo - $s_diario_neto;

        // ================== FECHA
        date_default_timezone_set('America/Mexico_City');

        $dia = date('d');
        $mesNum = date('m');
        $anio = date('Y');

        $meses = [
            '01' => 'enero', '02' => 'febrero', '03' => 'marzo',
            '04' => 'abril', '05' => 'mayo', '06' => 'junio',
            '07' => 'julio', '08' => 'agosto', '09' => 'septiembre',
            '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre',
        ];

        $mes = $meses[$mesNum];

        // ================== GEOLOCALIZACIÓN
        $ip = request()->ip();

        $ciudad = '';
        $estado = '';
        $lugar = '';

        try {
            $response = file_get_contents("https://ipwho.is/{$ip}");
            $data = json_decode($response, true);

            if ($data && $data['success']) {
                $ciudad = $data['city'] ?? '';
                $estado = $data['region'] ?? '';
                $lugar = "$ciudad, $estado.";
            }
            if(!$estado || !$ciudad){
                $lugar = '';
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        $textoFecha = "$lugar A $dia de $mes del $anio";

        // ================== LOGO
        $logo = public_path('images/logo.png');
        
        // ================== SELECCIÓN DE PLANTILLA
        if ($datos->type_salary_adjustment_movement_id == 2) {
            $view = 'pdf.pcp_nivelacion';
            $nombre = $id.'_AJUSTE_DE_SUELDO-NIVELACION.pdf';
        } else {
            $view = 'pdf.pcp';
            $nombre = $id.'_AJUSTE_DE_SUELDO-PROMOCION_Y_CAMBIO_DE_PUESTO.pdf';
        }

        // ================== HTML
        $html = view($view, compact(
            'datos',
            'logo',
            'textoFecha',
            's_diario',
            's_diario_neto',
            'comp_actual',
            'neto_sem_actual',
            'sueldo_nuevo',
            'neto_nuevo',
            'comp_nuevo',
            'semanal_nuevo',
            'sueldo_dif',
            'neto_dif',
            'comp_dif',
            'semanal_dif'
        ))->render();

        // ================== PDF
        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 15,
        ]);

        $mpdf->WriteHTML($html);

        // solo pestaña nueva
        // return response($mpdf->Output($nombre, "S"))
        //     ->header('Content-Type', 'application/pdf')
        //     ->header('Content-Disposition', 'inline; filename="'.$nombre.'"');

        // solo descarga
        return response()->streamDownload(
            fn() => print($mpdf->Output('', 'S')),
            $nombre
        );
    }

    // Formato de evaluación
    public function getEvaluation($id)
    {
        ini_set("pcre.backtrack_limit", "100000000");
        ini_set('memory_limit', '-1');

        $mpdf = new Mpdf(['format' => 'A4', 'margin_top' => 15]);

        $datos = DB::table('employee_salary_adjustments as esa')
            ->join('branch_offices as bo', 'bo.id', '=', 'esa.branch_office_id')
            ->join('employees as e', 'e.id', '=', 'esa.employee_id')
            ->join('departments as d', 'd.id', '=', 'esa.new_department_id')
            ->select(
                'esa.*',
                'bo.name as planta',
                'e.full_name as empleado',
                'e.entry_date as fecha_ingreso',
                'd.name as departamento'
            )
            ->whereNull('esa.deleted_at')
            ->whereNull('e.deleted_at')
            ->where('esa.id', $id)
            ->first();

        if (!$datos) {
            abort(404);
        }

        date_default_timezone_set('America/Mexico_City');

        $fechaContrato = $datos->fecha_contrato ?? now();
        $timestamp = strtotime($fechaContrato);

        $dia = date('d', $timestamp);
        $mesNum = date('m', $timestamp);
        $anio = date('Y', $timestamp);

        $meses = [
            '01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril',
            '05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto',
            '09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre',
        ];

        $mes = $meses[$mesNum];

        // 🌎 Ubicación
        $ip = request()->ip();

        try {
            $response = file_get_contents("https://ipwho.is/{$ip}");
            $data = json_decode($response, true);

            $ciudad = $data['city'] ?? '';
            $estado = $data['region'] ?? '';
            $lugar = "$ciudad, $estado.";
            if(!$estado || !$ciudad){
                $lugar = '';
            }
        } catch (\Exception $e) {
            $ciudad = 'N/A';
            $estado = 'N/A';
        }

        $textoFecha = "$lugar A $dia de $mes del $anio";

        // 📊 Evaluación
        $resultados = json_decode($datos->evaluacion_json ?? '{}', true) ?? [];

        $areas = [
            'cumplimiento' => ['title'=>'CUMPLIMIENTO Y RESULTADOS','weight'=>0.30,'questions'=>['res_1','res_2','res_3']],
            'conocimientos' => ['title'=>'CONOCIMIENTOS Y HABILIDADES TÉCNICAS','weight'=>0.25,'questions'=>['con_1','con_2','con_3']],
            'actitudes' => ['title'=>'ACTITUDES Y COMPORTAMIENTOS','weight'=>0.20,'questions'=>['act_1','act_2','act_3']],
            'trabajo' => ['title'=>'TRABAJO EN EQUIPO Y CULTURA ORGANIZACIONAL','weight'=>0.15,'questions'=>['team_1','team_2','team_3']],
            'potencial' => ['title'=>'POTENCIAL Y PROYECCIÓN','weight'=>0.10,'questions'=>['pot_1','pot_2','pot_3']],
        ];

        $preguntas = [
            'res_1' => 'Cumple con los objetivos y metas asignadas en tiempo y forma?',
            'res_2' => 'El colaborador ha sido en la calidad de su trabajo durante el período de prueba?',
            'res_3' => 'Se adapta a los procedimientos y estándares de la planta/área',
            'con_1' => 'Demuestra dominio de las herramientas, procesos o equipos requeridos para su puesto?',
            'con_2' => 'Cumple con la autonomía al muestro al realizar sus tareas?',
            'con_3' => 'Ha requerido supervisión constante o ha mostrado iniciativa para resolver problemas?',
            'act_1' => 'Mantiene una actitud positiva y profesional frente a los retos?',
            'act_2' => 'Muestra disposición para aprender y aplicar retroalimentación?',
            'act_3' => 'Es puntual y cumple con horarios y normativas de la empresa',
            'team_1' => 'Se integra adecuadamente con sus compañeros y colabora en equipo?',
            'team_2' => 'Respeta las normas de seguridad, higiene y cultura de la org.',
            'team_3' => 'Su comportamiento refleja los valores de la empresa?',
            'pot_1' => 'Qué tan considerable consideras que puede crecer y asumir mayores responsabilidades?',
            'pot_2' => 'El colaborador demuestra liderazgo o influencia positiva en :',
            'pot_3' => 'El colaborador demuestra interés en seguir desarrollándose y aprendiendo nuevas competencias para aportar más valor a la organización?',
        ];

        $puntos_obtenidos = [];
        $total_puntos = 0;
        $porcentaje_final = 0;

        foreach ($areas as $key => $area) {
            $suma = 0;

            foreach ($area['questions'] as $q) {
                $suma += (int)($resultados[$q] ?? 0);
            }

            $porcentaje = ($suma / 15) * 100 * $area['weight'];

            $puntos_obtenidos[$key] = [
                'puntos' => $suma,
                'porcentaje' => $porcentaje
            ];

            $total_puntos += $suma;
            $porcentaje_final += $porcentaje;
        }

        // 🧠 Resultado final
        if ($total_puntos >= 65) {
            $resultado = ['texto'=>'Desempeño Sobresaliente','color'=>'green','detalle'=>'Otorgar planta.'];
        } elseif ($total_puntos >= 50) {
            $resultado = ['texto'=>'Desempeño Adecuado','color'=>'green','detalle'=>'Plan de mejora.'];
        } elseif ($total_puntos >= 40) {
            $resultado = ['texto'=>'Desempeño Limitado','color'=>'orange','detalle'=>'Extender prueba.'];
        } else {
            $resultado = ['texto'=>'Desempeño Insuficiente','color'=>'red','detalle'=>'No otorgar planta.'];
        }

        // 🧾 Render Blade
        $html = view('pdf.pcp_evaluacion', compact(
            'datos','textoFecha','areas','preguntas',
            'resultados','puntos_obtenidos',
            'total_puntos','porcentaje_final','resultado'
        ))->render();

        $mpdf->WriteHTML($html);

        // mostrar en lugar de descargar
        // return response($mpdf->Output('', 'S'))
        //     ->header('Content-Type', 'application/pdf')
        //     ->header('Content-Disposition', 'inline; filename="reporte.pdf"');

        return response()->streamDownload(
            fn() => print($mpdf->Output('', 'S')),
            "{$id}_EVALUACIÓN_DE_DESEMPEÑO.pdf"
        );
    }

    // Formato de evaluación
    public function evaluation($id)
    {
        
    }
}
