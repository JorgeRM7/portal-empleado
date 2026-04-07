<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCompensation;
use App\Models\EmployeeDayVacation;
use App\Models\EmployeeOvertime;
use App\Models\EmployeeShiftRole;
use App\Models\PayrollInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Benefit;
use App\Models\Gender;
use App\Models\Departments;
use App\Models\Schedules;
use App\Models\User;
use App\Models\EmployeeSalaryAdjustment;
use App\Models\Address;
use App\Models\EmployeeCatalog;
use App\Models\State;
use App\Models\Location;
use App\Models\Position;
use App\Models\ShiftRole;
use App\Models\Country;
use App\Models\BranchOffice;
use App\Models\Bank;
use App\Models\City;
use App\Models\TaxSystem;
use App\Models\BenefitEmployee;
use App\Models\TaxData;
use App\Models\PaymentData;
use App\Models\Classification;
use App\Models\PaymentMethod;
use App\Models\BranchOfficeLocation;
use App\Models\StatusReason;
use App\Models\EmployeeSalaryPayment;
use App\Models\PayrollExtra;
use App\Models\EmployeeStatuse;
use App\Models\WeeklyAssistence;
use DB;
// use Logs;
use App\Models\Logs;
use Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ShiftRoleCycle;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;



class EmployeeCatalogController
{

    public function index()
    {

        $user = auth()->user();

        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $branchOfficeIds = $branchOffices->pluck('id');

        $departments = Departments::select('id', 'name')->get();

        $employees = Employee::select('id', 'full_name', 'branch_office_id')
            ->where('status', 'termination')
            ->whereIn('branch_office_id', $branchOfficeIds)
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('EmployeeCatalog/Index', [
            'branchOffices' => $branchOffices,
            'departments' => $departments,
            'employees' => $employees
        ]);
    }

    public function filter_data_catalog(Request $request)
    {
        // dd($request->all());
        $data = EmployeeCatalog::with([
            'paymentData',
            'gender',
            'position',
            'department',
            'state',
            'branchOffice',

            ])
            ->when($request->branch_office_id, function ($query) use ($request) {
                $query->whereIn('branch_office_id', (array) $request->branch_office_id);
            })
            ->when($request->estado, function ($query) use ($request) {
                return $query->whereIn('status', (array)$request->estado);
            })
            ->when($request->department_id, function ($query) use ($request) {
                return $query->whereIn('department_id', (array)$request->department_id);
            })
            ->when($request->date_from, function ($query) use ($request) {
                $query->whereDate('entry_date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($query) use ($request) {
                $query->whereDate('entry_date', '<=', $request->date_to);
            })
            ->when($request->employees, function ($query) use ($request) {
                $query->whereIn('id', (array) $request->employees);
            })
            ->when(filter_var($request->sinRol, FILTER_VALIDATE_BOOLEAN), function ($query) {
                $query->whereNull('shift_role_id');
            })
            ->get();

            $employees = Employee::select('id', 'full_name')
                ->when($request->branch_office_id, function ($query) use ($request) {
                    if (is_array($request->branch_office_id)) {
                        return $query->whereIn('branch_office_id', $request->branch_office_id);
                    }
                    return $query->where('branch_office_id', $request->branch_office_id);
                })
                ->orderBy('id', 'asc')
                ->get();


        return response()->json([
            'data' => $data,
            'employees' => $employees
        ]);

    }

    public function export(Request $request)
    {
        $planta = $request->input('planta');
        $campos = $request->input('campos', []);
        $ids = $request->input('ids_exportar', []);

        $data = EmployeeCatalog::export_data($planta, $campos, $ids);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos para exportar'
            ], 400);
        }

        $filename = 'catalogo_empleados_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 🔹 Encabezados (tomamos las llaves del primer registro)
            fputcsv($handle, array_keys((array) $data[0]));

            // 🔹 Filas
            foreach ($data as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function create()
    {
        $Departments            = Departments::select('id', 'name')->get();
        $Cities                 = City::select('id', 'name', 'state_id')->get();
        $BranchOffices          = BranchOffice::select('id', 'code')->get();
        $Genders                = Gender::select('id', 'name')->get();
        $Benefits               = Benefit::select('id', 'name')->get();
        $Schedules              = Schedules::select('id', 'name')->get();
        $Banks                  = Bank::select('id', 'name')->get();
        $States                 = State::select('id', 'name')->get();
        // $Location       = Location::select('id', 'name')->get();
        $Position               = Position::select('id', 'name')->get();
        $ShiftRole              = ShiftRole::select('id', 'name')->get();
        $Country                = Country::select('id', 'name')->get();
        $TaxSystem              = TaxSystem::select('id', 'name')->get();
        $PaymentMethod          = PaymentMethod::select('id', 'name')->get();
        $Classification         = Classification::select('id', 'description')->get();
        $BranchOfficeLocation   = BranchOfficeLocation::select('id', 'name')->get();

        return Inertia::render('EmployeeCatalog/Create', [

            'states'                    => $States,
            'cities'                    => $Cities,
            // 'locations' =>$Location,
            'countries'                 =>$Country,
            'tax_systems'               =>$TaxSystem,
            'banks'                     =>$Banks,
            'payment_methods'           =>$PaymentMethod,
            'positions'                 =>$Position,
            'branch_offices'            =>$BranchOffices,
            'branch_offices_locations'  =>$BranchOfficeLocation,
            'departments'               =>$Departments,
            'benefits'                  =>$Benefits,
            'clasificacions'            =>$Classification,
            'shift_roles'               =>$ShiftRole,
            'genders'                   => $Genders,

        ]);
    }

    public function store(Request $request)
    {
        // dd($request);

        $request->validate([
            'employee_id' => 'required',
            'dni'         => 'required',
            'health_id'   => 'required',
            'tax_id'      => 'required',
        ],[
            'employee_id.required' => 'El ID de empleado es obligatorio.',
            'dni.required'         => 'El DNI es obligatorio.',
            'health_id.required'   => 'El NSS/CURP es obligatorio.',
            'tax_id.required'      => 'El RFC es obligatorio.',
        ]);


        $existingEmployee = Employee::query()
        ->when($request->employee_id, fn($q) => $q->orWhere('id', $request->employee_id))
        ->when($request->dni, fn($q) => $q->orWhere('dni', $request->dni))
        ->when($request->health_id, fn($q) => $q->orWhere('health_id', $request->health_id))
        ->first();

        $taxExists = TaxData::where('tax_id', $request->tax_id)->exists();

        $errors = [];

        if ($existingEmployee) {

            if ($request->employee_id && $existingEmployee->id == $request->employee_id) {
                $errors['employee_id'] = "Ya existe un empleado con el ID {$existingEmployee->id} ({$existingEmployee->full_name}).";
            }

            if ($request->dni && $existingEmployee->dni == $request->dni) {
                $errors['dni'] = "El DNI ya está registrado para {$existingEmployee->full_name}.";
            }

            if ($request->health_id && $existingEmployee->health_id == $request->health_id) {
                $errors['health_id'] = "El NSS ya está registrado para {$existingEmployee->full_name}.";
            }
        }


        if ($taxExists) {
            $errors['tax_id'] = "El RFC {$request->tax_id} ya se encuentra registrado en el sistema.";
        }

        if (!empty($errors)) {
            return back()
                ->withErrors($errors)
                ->withInput();
        }

        DB::beginTransaction();

        try {

            $inserted = [
                'employee' => null,
                'address'  => null,
                'taxData'  => null,
                'payment'  => null,
                'benefits' => [],
            ];

            $additional_info = [
                "user_id"                   => $request->user_data->id ?? null,
                "additional_phone"          => $request->additional_phone,
                "additional_email"          => $request->additional_email,
                "profession"                => $request->profession,
                "level_education"           => $request->level_education,
                "civil_state"               => $request->civil_state,
                "unit_health"               => $request->unit_health,
                "emergency_name"            => $request->emergency_name,
                "emergency_phone"           => $request->emergency_phone,
                "employee_type"             => $request->employee_type,
                "job_type"                  => $request->job_type,
                'shift_role_id'             => $request->shift_role_id,
                "employer_registration"     => $request->employer_registration,
                "medical_unit"              => $request->medical_unit,
                "pension_discount"          => $request->pension_discount,
                "clasification"             => $request->classification_id,
                "tipo_ingresos"             => $request->type_income,
                "clave_regimen"             => $request->regime_key,
                "base_cotizacion"           => $request->contribution_basis,
                "tipo_contrato"             => $request->type_contract,
                "ruta_plantilla"            => $request->ruta_plantilla,
                "clave_regimen_cntrato"     => $request->clave_regimen_cntrato,
                "tabla_vacaciones"          => $request->holiday_table,
                "tabla_salario_diario"      => $request->tabla_salario_diario,
                "days_duration"             => $request->days_duration,
                "beneficiary_name"          => $request->beneficiary_name,
                "porcentaje_beneficiario"   => $request->porcentaje_beneficiario,
                "beneficiary_kinship"       => $request->beneficiary_kinship,
                "nombre_beneficiario2"      => $request->nombre_beneficiario2,
                "porcentaje_beneficiario2"  => $request->porcentaje_beneficiario2,
                "parentesco_beneficiario2"  => $request->parentesco_beneficiario2,
                "external_id"               => $request->external_id
            ];

            $Employee = Employee::create([
                'id'                        => $request->employee_id,
                'code'                      => $request->employee_id,
                'external_id'               => $request->external_id,
                'name'                      => $request->name,
                'surname'                   => $request->surname,
                'mother_surname'            => $request->mother_surname,
                'email'                     => $request->email,
                'state_id'                  => $request->birth_state_id,
                'personal_phone'            => $request->personal_phone,
                'company_phone'             => $request->company_phone,
                'branch_office_location_id' => $request->branch_office_location_id,
                'status'                    => 'entry',
                'dni'                       => $request->dni,
                'health_id'                 => $request->health_id,
                'department_id'             => $request->department_id,
                'position_id'               => $request->position_id,
                'employee_parent_id'        => $request->employee_parent_id,
                'employee_parent_email'     => $request->employee_parent_email,
                'gender_id'                 => $request->gender_id,
                'user_id'                   => $request->user_data->id ?? null,
                'branch_office_id'          => $request->branch_office_id,
                'birthday'                  => $request->birthday ? Carbon::parse($request->birthday)->format('Y-m-d') : null,
                'entry_date'                => $request->entry_date ? Carbon::parse($request->entry_date)->format('Y-m-d') : null,
                'termination_date'          => $request->termination_date,
                'company_email'             => $request->company_email ?? null,
                'rehireable'                => 1,
                'classification_id'         => $request->classification_id,
                'company_id'                => 1,
                'additional_info'           => $additional_info
            ]);

            $inserted['employee'] = [
                'id'   => $Employee->id,
                'dni'  => $Employee->dni,
                'name' => $Employee->full_name ?? ($Employee->name.' '.$Employee->surname),
            ];

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'employees',
                'date'            => Carbon::now('America/Mexico_City'),
                'old_data'        => null,
                'relationship_id' => $Employee->id,
                'new_data'        => json_encode($Employee->getAttributes())
            ]);

            $shiftRole = EmployeeShiftRole::create([
                'employee_id'      => $Employee->id,
                'shift_role_id'    => $request->shift_role_id,
                'start_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                'end_date'         => null,
                'active'           => 1,
                'branch_office_id' => $request->branch_office_id
            ]);

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_shift_roles',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $shiftRole->id,
                'new_data'        => json_encode($shiftRole->getAttributes())
            ]);

            $shiftRoleCycle = ShiftRoleCycle::create([
                'employee_id'   => $Employee->id,
                'shift_role_id' => $request->shift_role_id,
                'date'          => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                'started_at'    => Carbon::now('America/Mexico_City'),
                'ends_at'       => null
            ]);

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_shift_role_cycles',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $shiftRoleCycle->id,
                'new_data'        => json_encode($shiftRoleCycle->getAttributes())
            ]);

            $rawLocation = is_array($request->location_data)
                ? ($request->location_data['name'] ?? null)
                : $request->location_data;

            $nombreColonia = trim($request->location_name ?? $rawLocation);

            if ($nombreColonia) {
                $location = Location::firstOrCreate(
                    [
                        'name'        => $nombreColonia,
                        'postal_code' => $request->postal_code
                    ],
                    [
                        'city_id'     => $request->city_id,
                        'state_id'    => $request->state_id,
                    ]
                );

                $Address = Address::create([
                    'street'           => $request->street,
                    'external_number'  => $request->external_number,
                    'internal_number'  => $request->internal_number,
                    'location_id'      => $location->id,
                    'city_id'          => $request->city_id,
                    'state_id'         => $request->state_id,
                    'country_id'       => $request->country_id,
                    'postal_code'      => $request->postal_code,
                    'addressable_type' => Employee::class,
                    'addressable_id'   => $Employee->id
                ]);

                $inserted['address'] = [
                    'id'          => $Address->id,
                    'street'      => $Address->street,
                    'postal_code' => $Address->postal_code,
                ];

                Logs::create([
                    'action'          => 'INSERT',
                    'user_id'         => auth()->id(),
                    'table_name'      => 'addresses',
                    'date'            => Carbon::now('America/Mexico_City'),
                    'relationship_id' => $Address->id,
                    'new_data'        => json_encode($Address->getAttributes())
                ]);
            }

            $TaxData = TaxData::create([
                'tax_id'        => $request->tax_id,
                'postal_code'   => $request->postal_code_tax,
                'email'         => $request->email,
                'tax_system_id' => $request->tax_system_id,
                'owner_type'    => Employee::class,
                'owner_id'      => $Employee->id
            ]);
            $inserted['taxData'] = [
                'id'     => $TaxData->id,
                'tax_id' => $TaxData->tax_id,
            ];

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'tax_data',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $TaxData->id,
                'new_data'        => json_encode($TaxData->getAttributes())
            ]);

            $Payment = PaymentData::create([
                'account_number'        => $request->account_number,
                'account_card'          => $request->account_card,
                'account_code'          => $request->account_code,
                'salary'                => $request->salary,
                'daily_salary'          => $request->daily_salary,
                'meta'                  => [
                    'weekly_salary'     => $request->weekly_salary
                ],
                'bank_id'               => $request->bank_id,
                'salary_type_id'        => null,
                'payment_method_id'     => $request->payment_method_id,
                'external_account_id'   => null,
                'owner_id'              => $Employee->id,
                'owner_type'            => Employee::class
            ]);

            $inserted['payment'] = [
                'id' => $Payment->id,
                'bank_id' => $Payment->bank_id,
                'account_number' => $Payment->account_number,
            ];

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'payment_data',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $Payment->id,
                'new_data'        => json_encode($Payment->getAttributes())
            ]);


            foreach ($request->benefits as $benefit) {
                $be = BenefitEmployee::create([
                    'employee_id' => $Employee->id,
                    'benefit_id'  => $benefit
                ]);

                $inserted['benefits'][] = [
                    'benefit_id' => $be->benefit_id
                ];

                Logs::create([
                    'action'          => 'INSERT',
                    'user_id'         => auth()->id(),
                    'table_name'      => 'benefit_employee',
                    'date'            => Carbon::now('America/Mexico_City'),
                    'relationship_id' => $be->id,
                    'new_data'        => json_encode($be->getAttributes())
                ]);
            }

            $fullName = trim("{$request->name} {$request->surname} {$request->mother_surname}");

            $firstName = strtoupper(explode(' ', trim($request->name))[0]);
            $firstSurname = strtoupper(trim($request->surname));
            $secondSurname = strtoupper(trim($request->mother_surname));

            $username = "{$firstName}.{$firstSurname}";

            if (User::where('username', $username)->exists()) {
                $username = "{$firstName}.{$secondSurname}";

                if (User::where('username', $username)->exists()) {
                    $username = "{$firstName}_{$firstSurname}";

                    if (User::where('username', $username)->exists()) {
                        $username = $username . $Employee->id;
                    }
                }
            }

            $user = User::create([
                'name'                      => $fullName,
                'username'                  => $username,
                'email'                     => $request->email,
                'password'                  => Hash::make($request->employee_id),
                'current_branch_office_id'  => $request->branch_office_id,
                'default_locale'            => 'es',
                'email_verified_at'         => now(),
            ]);

            $Employee->update(['user_id' => $user->id]);

            $currentInfo = $Employee->additional_info ?? [];
            $currentInfo['user_id'] = $user->id;
            $Employee->update([
                'additional_info' => $currentInfo
            ]);

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'users',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $user->id,
                'new_data'        => json_encode($user->getAttributes())
            ]);

            DB::commit();
            return redirect()->route('catalog')
                ->with('success', 'Empleado creado correctamente');
            // return response()->json([
            //     'success'  => true,
            //     'message'  => 'Empleado creado correctamente',
            //     'inserted' => $inserted
            // ], 200);


        } catch (\Exception $e) {
            DB::rollBack();
            // return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            return back()->withErrors(['error' => $e->getMessage() . " en línea " . $e->getLine()]);
            // dd($e->getMessage());
        }
    }

    public function show(string $id)
    {
        $detalles = EmployeeCatalog::show_full($id);

        $employee = Employee::find($id);
        return Inertia::render('EmployeeCatalog/Show', [
            'employee' => $employee,
            'data' => $detalles,
        ]);
    }

    public function edit(string $id)
    {

        $employee = Employee::with([
            'paymentData',
            'taxData',
            'benefits:id'
        ])->findOrFail($id);

        $addresses = Address::select(
            'street',
            'external_number',
            'internal_number',
            'location_id',
            'city_id',
            'state_id',
            'country_id',
            'postal_code'
        )
            ->where('addressable_id', $id)
            ->get();
        // dd($addresses);

        $data_employee = [
            "personal_data" => [
                "employee_id"       => $employee->id,
                "external_id"       => $employee->external_id,
                "email"             => $employee->email,
                "surname"           => $employee->surname,
                "mother_surname"    => $employee->mother_surname,
                "personal_phone"    => $employee->personal_phone,
                "dni"               => $employee->dni,
                "health_id"         => $employee->health_id,
                "birthday"          => $employee->birthday,
                "full_name"         => $employee->full_name,
                "gender_id"         => $employee->gender_id,
                "name"              => $employee->name,
                "birth_state_id"    => $employee->state_id,
            ],

            "address_data" => $addresses,

            "payment_data" => [
                "bank_id"               => $employee->paymentData?->bank_id,
                "account_number"        => $employee->paymentData?->account_number,
                "account_card"          => $employee->paymentData?->account_card,
                "account_code"          => $employee->paymentData?->account_code,
                "salary"                => $employee->paymentData?->salary,
                "daily_salary"          => $employee->paymentData?->daily_salary,
                "salary_type_id"        => $employee->paymentData?->salary_type_id,
                "payment_method_id"     => $employee->paymentData?->payment_method_id,
                "external_account_id"   => $employee->paymentData?->external_account_id,
                "weekly_salary"         => $employee->paymentData?->meta['weekly_salary'] ?? null,
            ],
            "fiscal_data" => [
                "tax_id"            => $employee->taxData?->tax_id,
                "postal_code_tax"   => $employee->taxData?->postal_code,
                "tax_system_id"     => $employee->taxData?->tax_system_id,
            ],

            "employment_data" => [
                "id"                        => $employee->id,
                "status"                    => $employee->status,
                "company_phone"             => $employee->company_phone,
                "company_id"                => $employee->company_id,
                "department_id"             => $employee->department_id,
                "position_id"               => $employee->position_id,
                "employee_parent_id"        => $employee->employee_parent_id,
                "employee_parent_email"     => $employee->employee_parent_email,
                "shift_role_id"             => $employee->shift_role_id,
                "branch_office_id"          => $employee->branch_office_id,
                "entry_date"                => $employee->entry_date,
                "termination_date"          => $employee->termination_date,
                "branch_office_location_id" => $employee->branch_office_location_id,
                "rehireable"                => $employee->rehireable,
                "company_email"             => $employee->company_email,
                "reentry_date"              => $employee->reentry_date,
                "transfer_date"             => $employee->transfer_date,
                "classification_id"         => $employee->classification_id,
                "benefits"                  => $employee->benefits->map(fn($item) => ["benefit_id" => $item->id]),
            ],

            // "additional_info" => $employee->additional_info,

            "additional_info" => [
                "additional_phone"         => $employee->additional_info['additional_phone'] ?? null,
                "additional_email"         => $employee->additional_info['additional_email'] ?? null,
                "profession"               => $employee->additional_info['profession'] ?? null,
                "level_education"          => $employee->additional_info['level_education'] ?? null,
                "civil_state"              => $employee->additional_info['civil_state'] ?? null,

                "beneficiary_name"         => $employee->additional_info['beneficiary_name'] ?? null,
                "beneficiary_kinship"      => $employee->additional_info['beneficiary_kinship'] ?? null,
                "porcentaje_beneficiario"  => $employee->additional_info['porcentaje_beneficiario'] ?? null,

                "nombre_beneficiario2"     => $employee->additional_info['nombre_beneficiario2'] ?? null,
                "porcentaje_beneficiario2" => $employee->additional_info['porcentaje_beneficiario2'] ?? null,
                "parentesco_beneficiario2" => $employee->additional_info['parentesco_beneficiario2'] ?? null,

                "unit_health"              => $employee->additional_info['unit_health'] ?? null,
                "emergency_name"           => $employee->additional_info['emergency_name'] ?? null,
                "emergency_phone"          => $employee->additional_info['emergency_phone'] ?? null,
                "employee_type"            => $employee->additional_info['employee_type'] ?? null,
                "job_type"                 => $employee->additional_info['job_type'] ?? null,
                "employer_registration"    => $employee->additional_info['employer_registration'] ?? null,
                "medical_unit"             => $employee->additional_info['medical_unit'] ?? null,
                "pension_discount"         => $employee->additional_info['pension_discount'] ?? null,
                "type_income"              => $employee->additional_info['tipo_ingresos'] ?? null,
                "regime_key"               => $employee->additional_info['clave_regimen'] ?? null,
                "contribution_basis"       => $employee->additional_info['base_cotizacion'] ?? null,
                "type_contract"            => $employee->additional_info['tipo_contrato'] ?? null,
                "holiday_table"            => $employee->additional_info['tabla_vacaciones'] ?? null,
                "tabla_salario_diario"     => $employee->additional_info['tabla_salario_diario'] ?? null,
                "days_duration"            => $employee->additional_info['days_duration'] ?? null,
                "user_id"                  => $employee->additional_info['user_id'] ?? null,
                "ruta_plantilla"           => $employee->additional_info['ruta_plantilla'] ?? null,
                "clave_regimen_cntrato"    => $employee->additional_info['clave_regimen_cntrato'] ?? null,
                "shift_role_id"            => $employee->additional_info['shift_role_id'] ?? null,

            ],
        ];

        $Departments            = Departments::select('id', 'name')->get();
        $Cities                 = City::select('id', 'name', 'state_id')->get();
        $BranchOffices          = BranchOffice::select('id', 'code')->get();
        $Genders                = Gender::select('id', 'name')->get();
        $Benefits               = Benefit::select('id', 'name')->get();
        $Schedules              = Schedules::select('id', 'name')->get();
        $Banks                  = Bank::select('id', 'name')->get();
        $States                 = State::select('id', 'name')->get();
        // $Location               = Location::select('id', 'name')->get();
        $Position               = Position::select('id', 'name')->get();
        $ShiftRole              = ShiftRole::select('id', 'name')->get();
        $Country                = Country::select('id', 'name')->get();
        $TaxSystem              = TaxSystem::select('id', 'name')->get();
        $PaymentMethod          = PaymentMethod::select('id', 'name')->get();
        $Classification         = Classification::select('id', 'description')->get();
        $BranchOfficeLocation   = BranchOfficeLocation::select('id', 'name')->get();
        $StatusReason           = StatusReason::select('id', 'name', 'type')->get();

        return Inertia::render('EmployeeCatalog/Edit', [
            'employee'                  => $data_employee,
            'states'                    => $States,
            'cities'                    => $Cities,
            'countries'                 =>$Country,
            'tax_systems'               =>$TaxSystem,
            'banks'                     =>$Banks,
            'payment_methods'           =>$PaymentMethod,
            'positions'                 =>$Position,
            'branch_offices'            =>$BranchOffices,
            'branch_offices_locations'  =>$BranchOfficeLocation,
            'departments'               =>$Departments,
            'benefits'                  =>$Benefits,
            'employees'                 =>['id'=>1,'name'=>'miguel es puto'],
            'clasificacions'            =>$Classification,
            'users'                     =>['id'=>1,'name'=>'miguel es puto'],
            'shift_roles'               =>$ShiftRole,
            'genders'                   => $Genders,
            'reasons'                   => $StatusReason,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request);
        $request->validate([
            'employee_id' => 'required',
            'dni'         => 'required',
            'health_id'   => 'required',
            'tax_id'      => 'required',
        ]);

        $Employee = Employee::findOrFail($id);

        $existingEmployee = Employee::query()
            ->where('id','!=',$Employee->id)
            ->when($request->employee_id, fn($q)=>$q->orWhere('id',$request->employee_id))
            ->when($request->dni, fn($q)=>$q->orWhere('dni',$request->dni))
            ->when($request->health_id, fn($q)=>$q->orWhere('health_id',$request->health_id))
            ->first();

        $taxExists = TaxData::where('tax_id',$request->tax_id)
            ->where('owner_id','!=',$Employee->id)
            ->exists();

        $errors = [];

        if ($existingEmployee) {
            if ($existingEmployee->dni == $request->dni) {
                $errors['dni'] = "El DNI ya está registrado para {$existingEmployee->full_name}.";
            }

            if ($existingEmployee->health_id == $request->health_id) {
                $errors['health_id'] = "El NSS ya está registrado para {$existingEmployee->full_name}.";
            }
        }

        if ($taxExists) {
            $errors['tax_id'] = "El RFC ya está registrado en el sistema.";
        }

        if (!empty($errors)) {
            return response()->json([
                'success'=>false,
                'errors'=>$errors
            ],422);
        }

        DB::beginTransaction();

        try {

            // dd($request->shift_role_id);

            $current = $Employee->additional_info ?? [];

            $newStructure = [
                "user_id"                   => $request->user_id,
                "additional_phone"          => $request->additional_phone,
                "additional_email"          => $request->additional_email,
                "profession"                => $request->profession,
                "level_education"           => $request->level_education,
                "civil_state"               => $request->civil_state,
                "unit_health"               => $request->unit_health,
                "emergency_name"            => $request->emergency_name,
                "emergency_phone"           => $request->emergency_phone,
                "employee_type"             => $request->employee_type,
                "job_type"                  => $request->job_type,
                "employer_registration"     => $request->employer_registration,
                "medical_unit"              => $request->medical_unit,
                "pension_discount"          => $request->pension_discount,
                "shift_role_id"             => $request->shift_role_id,
                "tipo_ingresos"             => $request->type_income,
                "clave_regimen"             => $request->regime_key,
                "base_cotizacion"           => $request->contribution_basis,
                "tipo_contrato"             => $request->type_contract,
                "ruta_plantilla"            => $request->ruta_plantilla,
                "clave_regimen_cntrato"     => $request->clave_regimen_cntrato,
                "tabla_vacaciones"          => $request->holiday_table,
                "tabla_salario_diario"      => $request->tabla_salario_diario,
                "days_duration"             => $request->days_duration,
                "beneficiary_name"          => $request->beneficiary_name,
                "porcentaje_beneficiario"   => $request->porcentaje_beneficiario,
                "beneficiary_kinship"       => $request->beneficiary_kinship,
                "nombre_beneficiario2"      => $request->nombre_beneficiario2,
                "porcentaje_beneficiario2"  => $request->porcentaje_beneficiario2,
                "parentesco_beneficiario2"  => $request->parentesco_beneficiario2,
                "external_id"               => $request->external_id,
            ];

            $expectedKeys = array_keys($newStructure);
            $currentKeys = array_keys($current);

            $hasInvalidKeys = count(array_diff($currentKeys, $expectedKeys)) > 0;

            if (empty($current) || $hasInvalidKeys) {
                $additional_info = $newStructure;
            } else {
                $filtered = array_filter($newStructure, fn($value) => !is_null($value));

                $filtered['shift_role_id'] = $request->shift_role_id;

                $additional_info = array_merge($current, $filtered);
            }

            $Employee->update([
                'name'                      => $request->name,
                'surname'                   => $request->surname,
                'external_id'               => $request->external_id,
                'email'                     => $request->email,
                'personal_phone'            => $request->personal_phone,
                'company_phone'             => $request->company_phone,
                'status'                    => $request->status,
                'company_id'                => $request->company_id,
                'birth_state_id'            => $request->birth_state_id,
                'employee_parent_id'        => is_array($request->employee_parent_id)
                        ? implode(',', $request->employee_parent_id)
                        : $request->employee_parent_id,
                'employee_parent_email'     => $request->employee_parent_email,
                'mother_surname'            => $request->mother_surname,
                'dni'                       => $request->dni,
                'health_id'                 => $request->health_id,
                'department_id'             => $request->department_id,
                'position_id'               => $request->position_id,
                'additional_info'           => $additional_info,
                'gender_id'                 => $request->gender_id,
                "user_id"                   => $request->user_id,
                'branch_office_id'          => $request->branch_office_id,
                'birthday'                  => $request->birthday,
                'entry_date'                => $request->entry_date,
                'termination_date'          => $request->termination_date,
                'branch_office_location_id' => $request->branch_office_location_id,
                'classification_id'         => $request->classification_id,
            ]);

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'employees',
                'date'            => Carbon::now('America/Mexico_City'),
                'old_data'        => null,
                'relationship_id' => $Employee->id,
                'new_data'        => json_encode($Employee->getAttributes())
            ]);

            $shiftRole = EmployeeShiftRole::updateOrCreate(
                ['employee_id' => $Employee->id],
                [
                    'shift_role_id'    => $request->shift_role_id,
                    'start_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                    'end_date'         => null,
                    'active'           => 1,
                    'branch_office_id' => $request->branch_office_id
                ]
            );

            Logs::create([
                'action'          => $shiftRole->wasRecentlyCreated ? 'INSERT' : 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_shift_roles',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $shiftRole->id,
                'new_data'        => json_encode($shiftRole->getAttributes())
            ]);

            $shiftRoleCycle = ShiftRoleCycle::updateOrCreate(
                ['employee_id' => $Employee->id],
                [
                    'shift_role_id' => $request->shift_role_id,
                    'date'          => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                    'started_at'    => Carbon::now('America/Mexico_City'),
                    'ends_at'       => null
                ]
            );

            Logs::create([
                'action'          => $shiftRoleCycle->wasRecentlyCreated ? 'INSERT' : 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'employee_shift_role_cycles',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $shiftRoleCycle->id,
                'new_data'        => json_encode($shiftRoleCycle->getAttributes())
            ]);

            $locationName = is_array($request->location_data)
                ? ($request->location_data['name'] ?? null)
                : $request->location_data;

            $finalName = $locationName ?: $request->location_name;

            if ($request->location_id && is_numeric($request->location_id)) {
                $location = Location::find($request->location_id);
            } else {
                $location = Location::firstOrCreate(
                    [
                        'name'        => trim($finalName),
                        'postal_code' => $request->postal_code,
                    ],
                    [
                        'city_id'     => $request->city_id,
                        'state_id'    => $request->state_id,
                    ]
                );
            }

            $Address = Address::updateOrCreate(
                ['addressable_id' => $Employee->id],
                [
                    'street'          => $request->street,
                    'external_number' => $request->external_number,
                    'internal_number' => $request->internal_number,
                    'location_id'     => $location->id,
                    'city_id'         => $request->city_id,
                    'state_id'        => $request->state_id,
                    'country_id'      => $request->country_id,
                    'postal_code'     => $request->postal_code
                ]
            );

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'addresses',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $Address->id,
                'new_data'        => json_encode($Address->getAttributes())
            ]);

            $TaxData = TaxData::updateOrCreate(
                [
                    'owner_id'   => $Employee->id,
                ],
                [
                    'tax_id'          => $request->tax_id,
                    'postal_code'     => $request->postal_code_tax,
                    'email'           => $request->email,
                    'tax_system_id'   => $request->tax_system_id,
                ]
            );

            $paymentData = PaymentData::where('owner_id', $Employee->id)->first();
            $currentMeta = $paymentData?->meta ?? [];
            $newMeta = array_merge($currentMeta, [
                'weekly_salary' => $request->weekly_salary
            ]);

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => auth()->id(),
                'table_name'      => 'tax_data',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $TaxData->id,
                'new_data'        => json_encode($TaxData->getAttributes())
            ]);

            foreach ($request->benefits as $benefit) {
                $be = BenefitEmployee::updateOrCreate(
                    [
                        'employee_id' => $Employee->id,
                        'benefit_id'  => $benefit
                    ]
                );

                $action = $be->wasRecentlyCreated ? 'INSERT' : 'UPDATE';

                Logs::create([
                    'action'          => $action,
                    'user_id'         => auth()->id(),
                    'table_name'      => 'benefit_employee',
                    'date'            => Carbon::now('America/Mexico_City'),
                    'relationship_id' => $be->id,
                    'new_data'        => json_encode($be->getAttributes())
                ]);
            }

            $Payment = PaymentData::updateOrCreate(
                [
                    'owner_id' => $Employee->id,
                ],
                [
                    'account_number'    => $request->account_number,
                    'account_card'      => $request->account_card,
                    'account_code'      => $request->account_code,
                    'daily_salary'      => $request->daily_salary,
                    'salary'            => $request->salary,
                    'bank_id'           => $request->bank_id,
                    'payment_method_id' => $request->payment_method_id,
                    'meta'              => $newMeta,
                ]
            );

            Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'payment_data',
                'date'            => Carbon::now('America/Mexico_City'),
                'relationship_id' => $Payment->id,
                'new_data'        => json_encode($Payment->getAttributes())
            ]);

            $Employee->benefits()->sync($request->benefits ?? []);

            DB::commit();

            // return redirect()->back()->with('message', 'Empleado actualizado correctamente');
            return redirect()->route('catalog.edit', $Employee->id,)->with('success', 'Empleado actualizado');

        } catch (\Exception $e) {
            DB::rollBack();
            // Para errores de excepción, también regresamos con un error de sesión
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(string $id)
    {
        //
    }

    public function search(Request $request)
    {
        $q = $request->q;

        return Location::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                    ->orWhere('id', $q);
            })
            ->when($request->city_id, fn($s) => $s->where('city_id', $request->city_id))
            ->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$q])
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function searchEmploye(Request $request)
    {
        $q = $request->q;

        return Employee::query()
            ->where(function ($query) use ($q) {
                $query->where('id', 'like', "%$q%")
                    ->orWhere('name', 'like', "%$q%")
                    ->orWhere('surname', 'like', "%$q%")
                    ->orWhere('mother_surname', 'like', "%$q%")
                    ->orWhere('full_name', 'like', "%$q%");
            })
            ->limit(20)
            ->get(['id','full_name']);
    }

    public function reports( $data )
    {
        $employees = $data['empleados_list'] ?? [];

        if (empty($employees)) {
            return [];
        }

        $clean_ids = array_map('intval', $employees);

        $result = DB::table('employees')
            ->leftJoin('tax_data', 'tax_data.owner_id', '=', 'employees.id')
            ->leftJoin('payment_data', 'payment_data.owner_id', '=', 'employees.id')
            ->leftJoin('banks', 'banks.id', '=', 'payment_data.bank_id')
            ->leftJoin('branch_offices', 'branch_offices.id', '=', 'employees.branch_office_id')
            ->leftJoin('states', 'states.id', '=', 'employees.state_id')
            ->whereIn('employees.id', $clean_ids)
            ->select([
                'employees.health_id as NSS',
                'employees.entry_date as FECHA_ALTA',
                'tax_data.tax_id as RFC',
                'employees.dni as CURP',
                'employees.surname as PRIMER_APELLIDO',
                'employees.mother_surname as SEGUNDO_APELLIDO',
                'employees.name as NOMBRE',
                'employees.birthday as FECHA_NACIMIENTO',
                'payment_data.salary as SBC',
                'payment_data.daily_salary as SALARIO_DIARIO',
                'banks.name as BANCO',
                'employees.id as ID_EMPLEADO',
                'payment_data.account_number as NUMERO_CUENTA',
                'branch_offices.code as PLANTA',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(employees.additional_info, '$.clasification')) as CLASIFICACION"),
                'branch_offices.name',
                'states.name as LUGAR_NACIMIENTO',
                'payment_data.account_card as NUMERO_TARJETA',
                'payment_data.account_code as CLABE_INTERBANCARIA'
            ])
            ->groupBy('employees.id')
            ->get()
            ->toArray();

        return $result;
    }

    public function download(Request $request)
    {
        $request->validate([
            'empleados_list'    => 'required|array|min:1',
            'type'              => 'required|in:csv,excel',
            'report_name'       => 'nullable|string|max:100'
        ]);


        $data = $this->reports($request->all());
        $filename =$request->report_name.'_'.now()->format('Ymd_His');

        if (empty($data)) {
            return response()->json(['success'=>false,'message'=>'Sin datos para exportar'], 422);
        }

        $report = $request->report_name;

        switch ($report) {

            case 'VALIDACION_CUENTAS':

                $data = collect($data)->map(fn($item)=>[
                    "PLANTA"                => $item->PLANTA,
                    "CLASIFICACION"         => $item->CLASIFICACION,
                    "ID EMPLEADO"           => $item->ID_EMPLEADO,
                    "NOMBRE COMPLETO"       => "{$item->NOMBRE} {$item->PRIMER_APELLIDO} {$item->SEGUNDO_APELLIDO}",
                    "BANCO"                 => $item->BANCO,
                    "# TARJETA"             => $item->NUMERO_TARJETA ?? '',
                    "# CUENTA"              => $item->NUMERO_CUENTA ?? '',
                    "CLABE INTERBANCARIA"   => $item->CLABE_INTERBANCARIA ?? '',
                    "NOMBRE"                => $item->NOMBRE,
                    "APELLIDO PATERNO"      => $item->PRIMER_APELLIDO,
                    "APELLIDO MATERNO"      => $item->SEGUNDO_APELLIDO,
                ])->toArray();

                break;


            case 'ALTAS_IMSS':

                $data = collect($data)->map(fn($item)=>[
                    "NSS"               => $item->NSS,
                    "NOMBRE"            => $item->NOMBRE,
                    "PRIMER APELLIDO"   => $item->PRIMER_APELLIDO,
                    "SEGUNDO APELLIDO"  => $item->SEGUNDO_APELLIDO,
                    "CURP"              => $item->CURP,
                    "SBC (SDI)"         => $item->SBC,
                    "FECHA DE ALTA"     => optional($item->FECHA_ALTA)->format('d/m/Y'),
                    "SALARIO DIARIO"    => $item->SALARIO_DIARIO
                ])->toArray();

                break;


            case 'ALTAS_SUA':

                $data = collect($data)->map(fn($item)=>[
                    "NSS"                   => $item->NSS,
                    "FECHA DE ALTA"         => optional($item->FECHA_ALTA)->format('d/m/Y'),
                    "RFC"                   => $item->RFC,
                    "CURP"                  => $item->CURP,
                    "PRIMER APELLIDO"       => $item->PRIMER_APELLIDO,
                    "SEGUNDO APELLIDO"      => $item->SEGUNDO_APELLIDO,
                    "NOMBRE"                => $item->NOMBRE,
                    "SBC (SDI)"             => $item->SBC,
                    "FECHA DE NACIMIENTO"   => optional($item->FECHA_NACIMIENTO)->format('d/m/Y'),
                    "LUGAR DE NACIMIENTO"   => $item->LUGAR_NACIMIENTO,
                    "SALARIO DIARIO"        => $item->SALARIO_DIARIO
                ])->toArray();

                break;

            default:
                return response()->json([
                    'success'=>false,
                    'message'=>'Tipo de reporte no válido'
                ],422);
        }

        if ($request->type === 'csv') {
            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_keys((array)$data[0]));
                foreach ($data as $row) fputcsv($out, (array)$row);
                fclose($out);
            }, $filename.'.csv');
        }

        return Excel::download(new EmployeesExport(array_map(fn($r)=>(array)$r, $data)), $filename.'.xlsx');
    }

    public function downloadAll(Request $request)
    {
        $request->validate([
            'ids_exportar' => 'required|array|min:1'
        ]);

        $prefix = $request->report === 'NOI' ? 'NOI' : 'Empleados';
        $filename = $prefix . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($request) {

            $handle = fopen('php://output', 'w');
            $headerAdded = false;

            $rows = EmployeeCatalog::downloadAll([
                'ids_exportar' => $request->ids_exportar,
            ]);

            $contador = 0;

            foreach ($rows as $row) {

                $contador++;

                $row = (array) $row;
                $additional = json_decode($row['additional_info'] ?? '{}', true) ?? [];

                if ($request->report == 'NOI') {

                    $data = [
                        'Clave'                     => $row['Clave Empleado'] ?? null,
                        'Apellido Paterno'          => $row['Apellido Paterno'] ?? null,
                        'Apellido Materno'          => $row['Apellido Materno'] ?? null,
                        'Nombre'                    => $row['Nombre'] ?? null,
                        'Salario Diario'            => $row['Salario Diario'] ?? null,
                        'Genero'                    => $row['Genero'] ?? null,
                        'Fecha de nacimiento'       => $row['Fecha Nacimiento'] ?? null,
                        'Fecha de ingreso'          => $row['Fecha Ingreso'] ?? null,
                        'IMSS'                      => $row['NSS'] ?? null,
                        'Entidad de nacimiento'     => $row['Entidad Nacimiento'] ?? null,
                        'CURP'                      => $row['CURP'] ?? null,
                        'RFC'                       => $row['RFC'] ?? null,
                        'Calle'                     => $row['Calle'] ?? null,
                        'Colonia'                   => $row['Colonia'] ?? null,
                        'Ciudad'                    => $row['Ciudad'] ?? null,
                        'Codigo postal'             => $row['Codigo Postal'] ?? null,
                        'Codigo postal fiscal'      => $row['Codigo Postal Fiscal'] ?? null,
                        'Entidad Federativa'        => $row['Entidad Federativa'] ?? null,
                        'Telefono'                  => $row['Telefono Personal'] ?? null,
                        'Telefono 2'                => $additional['additional_phone'] ?? null,
                        'Correo del empleado'       => $row['Correo'] ?? null,
                        'Correo alternativo'        => $additional['additional_email'] ?? null,
                        'Nivel de estudios'         => $additional['level_education'] ?? null,
                        'Profesion'                 => $additional['profession'] ?? null,
                        'Estado civil'              => $additional['civil_state'] ?? null,
                        'Tipo de sangre'            => $additional['Tipo de sangre'] ?? null,
                        'Persona de emergencia'     => $additional['emergency_name'] ?? null,
                        'Telefono de emergencia'    => $additional['emergency_phone'] ?? null,
                        'Forma de pago'             => $row['Metodo Pago'] ?? null,
                        'Banco operador'            => $row['Banco'] ?? null,
                        'Sucursal'                  => $row['Ciudad'] ?? null,
                        'Control de banco'          => $row['Numero Cuenta'] ?? null,
                        'Cuenta de deposito'        => $row['Numero Cuenta'] ?? null,
                        'Tipo de empleado'          => $additional['employee_type'] ?? null,
                        'Tipo de jornada'           => $additional['job_type'] ?? null,
                        'Turno'                     => $additional['shift_id'] ?? null,
                        'Unidad Medica'             => $additional['unidad_medica'] ?? null,
                        'Descuento pensión'         => $additional['descuento_pension'] ?? null,
                        'Registro patronal'         => $additional['employer_registration'] ?? null,
                        'Clasificacion'             => $additional['clasification'] ?? null,
                        'Clave departamento'        => $row['Clave departamento'] ?? null,
                        'Clave puesto'              => $row['Clave puesto'] ?? null,
                        'Tipo de contrato'          => $additional['tipo_contrato'] ?? null,
                        'Tipo de ingresos'          => $additional['tipo_ingresos'] ?? null,
                        'Pais'                      => 'MEXICO',
                        'Clave de regimen'          => $additional['employer_registration'] ?? null,
                        'Base de cotizacion'        => $additional['base_cotizacion'] ?? null,
                        'Tipo de contrato imss'     => $additional['tipo_contrato'] ?? null,
                        'Tipo de jornada imss'      => $additional['job_type'] ?? null,
                        'Clave de entidad'          => $additional['Clave de entidad'] ?? null,
                        'Ruta plantilla'            => $additional['ruta_plantilla'] ?? null,
                        'Clave regimen de contrato' => $additional['clave_regimen_cntrato'] ?? null,
                        'Clave banco operador.'     => $row['Clave banco operador'] ?? null,
                        'Tabla vacaciones'          => $additional['tabla_vacaciones'] ?? null,
                        'Tabla salario diario'      => $additional['tabla_salario_diario'] ?? null,
                        'Modalidad de Trabajo'      => $additional['Modalidad de Trabajo'] ?? null,
                        'SDI'                       => $row['SDI'] ?? null,
                        'Dias duracion'             => $additional['Dias duracion'] ?? null,
                    ];

                } else {

                    $mapJson = [
                        'Usuario Asignado'          => $additional['user_id'] ?? null,
                        'Telefono Adicional'        => $additional['additional_phone'] ?? null,
                        'Correo Adicional'          => $additional['additional_email'] ?? null,
                        'Profeccion'                => $additional['profession'] ?? null,
                        'Nivel Estudios'            => $additional['level_education'] ?? null,
                        'Estado Civil'              => $additional['civil_state'] ?? null,
                        'Tipo Sangre'               => $additional['unit_health'] ?? null,
                        'Persona Emergencia'        => $additional['emergency_name'] ?? null,
                        'Telefono Emergencia'       => $additional['emergency_phone'] ?? null,
                        'Tipo Empleado'             => $additional['employee_type'] ?? null,
                        'Tipo Jornada'              => $additional['job_type'] ?? null,
                        'Turno'                     => $additional['shift_id'] ?? null,
                        'Unidad Medica'             => $additional['unidad_medica'] ?? null,
                        'Descuento Pension'         => $additional['descuento_pension'] ?? null,
                        'Registro Patronal'         => $additional['employer_registration'] ?? null,
                        'Clasificacion'             => $additional['clasification'] ?? null,
                        'Tipo Ingresos'             => $additional['tipo_ingresos'] ?? null,
                        'Clave Regimen'             => $additional['clave_regimen'] ?? null,
                        'Base Cotizacion'           => $additional['base_cotizacion'] ?? null,
                        'Tipo Contrato'             => $additional['tipo_contrato'] ?? null,
                        'Ruta Plantilla'            => $additional['ruta_plantilla'] ?? null,
                        'Clave Regimen Contrato'    => $additional['clave_regimen_cntrato'] ?? null,
                        'Tabla Vacaciones'          => $additional['tabla_vacaciones'] ?? null,
                        'Tabla Salario'             => $additional['tabla_salario_diario'] ?? null,
                        'Dias Duracion'             => $additional['days_duration'] ?? null,
                        'Nombre Beneficiario'       => $additional['beneficiary_name'] ?? null,
                        'Parentezco Beneficiario'   => $additional['beneficiary_kinship'] ?? null,
                        'Nombre Beneficiario 2'     => $additional['nombre_beneficiario2'] ?? null,
                        'Porcentaje Beneficiario 2' => $additional['porcentaje_beneficiario2'] ?? null,
                        'Parentezco Beneficiario 2' => $additional['parentesco_beneficiario2'] ?? null,
                    ];

                    $row = array_merge($row, $mapJson);
                    unset($row['additional_info']);

                    $campos = $request->campos ?? [];

                    if (!empty($campos)) {
                        $row = collect($row)->only($campos)->toArray();
                    }

                    $data = $row;
                }

                if (!$headerAdded) {
                    fputcsv($handle, array_keys($data));
                    $headerAdded = true;
                }

                fputcsv($handle, $data);

                // 👇 log cada 200 registros
                // if ($contador % 200 == 0) {
                //     \Log::info("Procesados: $contador");
                // }
            }

            fclose($handle);

        }, $filename);
    }

    public function reentry(Request $request) {
        DB::beginTransaction();

        try {
            $branch_office_id   = $request->branch_office_id;
            $duration_days      = $request->duration_days;
            $employee_id        = $request->employee_id;
            $reason_id          = $request->reason_id;
            $reentry_date       = $request->reentry_date;
            $end_training       = date('Y-m-d', strtotime("$reentry_date + $duration_days days"));
            $date               = Carbon::parse($reentry_date);
            $year               = $date->year;
            $week               = $date->isoWeek;
            $userId             = auth()->id();

            $employee = Employee::find($employee_id);
            if (!$employee) throw new \Exception("Empleado no encontrado");

            $beforeEmployee = [
                'status' => $employee->status,
                'branch_office_id' => $employee->branch_office_id,
                'reentry_date' => $employee->reentry_date
            ];

            $statusEntry = EmployeeStatuse::create([
                'employee_id'      => $employee_id,
                'user_id'          => $userId,
                'status'           => 'reentry',
                'date'             => $reentry_date,
                'reason_id'        => $reason_id,
                'branch_office_id' => $branch_office_id
            ]);

            $employee->update([
                'status'            => 'reentry',
                'reentry_date'      => $request->reentry_date,
                'branch_office_id'  => $request->branch_office_id,
            ]);

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => $userId,
                'table_name'      => 'employees',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode([
                    'before' => $beforeEmployee,
                    'after'  => $employee->getChanges()
                ])
            ]);

            Logs::create([
                'action'          => 'CREATE',
                'user_id'         => $userId,
                'table_name'      => 'employee_statuses',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode(['before' => null, 'after' => $statusEntry->toArray()])
            ]);

            $position = Position::find($employee->position_id);

            $adjustment = EmployeeSalaryAdjustment::create([
                'employee_id'                         => $employee_id,
                'branch_office_id'                    => $branch_office_id,
                'type_salary_adjustment_movement_id'  => 2,
                'actual_department_id'                => $employee->department_id,
                'actual_position_id'                  => $employee->position_id,
                'new_department_id'                   => $employee->department_id,
                'new_position_id'                     => $employee->position_id,
                'start_training'                      => $request->reentry_date,
                'end_training'                        => $end_training,
                'days_period'                         => $duration_days,
                'prev_salary'                         => $position->daily_salary ?? 0,
                'adjust_salary'                       => $position->daily_salary ?? 0,
                'date'                                => $request->reentry_date,
                'week_number'                         => $week,
                'week_year'                           => $year,
                'status'                              => 1,
            ]);

            Logs::create([
                'action'          => 'CREATE',
                'user_id'         => $userId,
                'table_name'      => 'employee_salary_adjustments',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode(['before' => null, 'after' => $adjustment->toArray()])
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Reingreso procesado correctamente');

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error: ' . $e->getMessage()]);
        }
    }

    public function transfer(Request $request){
        DB::beginTransaction();

        try {
            $branch_office_id   = $request->branch_office_id;
            $duration_days      = $request->duration_days;
            $employee_id        = $request->employee_id;
            $reason_id          = $request->reason_id;
            $termination_date   = $request->termination_date;
            $entry_date         = $request->entry_date;
            $content            = $request->observations;
            $date = Carbon::parse($entry_date);
            $year = $date->year;
            $week = $date->isoWeek;
            $userId             = auth()->id();


            $employee = Employee::find($employee_id);


            $oldBranchId = $employee->branch_office_id;
            $beforeEmployee = [
                'status' => $employee->status,
                'branch_office_id' => $oldBranchId,
                'termination_date' => $employee->termination_date,
            ];

            $statusTerm = EmployeeStatuse::create([
                'employee_id'      => $employee_id,
                'status'           => 'termination',
                'date'             => $termination_date,
                'reason_id'        => $reason_id,
                'branch_office_id' => $oldBranchId,
                'values'           => json_encode(['Planta Anterior' => $oldBranchId])
            ]);

            $statusEntry = EmployeeStatuse::create([
                'employee_id'      => $employee_id,
                'status'           => 'entry',
                'date'             => $entry_date,
                'content'          => $content,
                'reason_id'        => $reason_id,
                'branch_office_id' => $branch_office_id,
                'values'           => json_encode(['Planta Nueva' => $branch_office_id])
            ]);

            $employee->update([
                'status'             => 'change',
                'transfer_date'      => $entry_date,
                'branch_office_id'   => $branch_office_id,
                'termination_date'   => $termination_date,
            ]);

            Logs::create([
                'action'          => 'UPDATE',
                'user_id'         => $userId,
                'table_name'      => 'employees',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode([
                    'before' => $beforeEmployee,
                    'after' => $employee->getChanges()
                ])
            ]);

            Logs::create([
                'action'          => 'CREATE',
                'user_id'         => $userId,
                'table_name'      => 'employee_statuses',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode([
                    'info' => 'Salida por traspaso (Planta Anterior)',
                    'after' => $statusTerm->toArray()
                ])
            ]);

            Logs::create([
                'action'          => 'CREATE',
                'user_id'         => $userId,
                'table_name'      => 'employee_statuses',
                'date'            => now(),
                'relationship_id' => $employee->id,
                'old_data'        => json_encode([
                    'info' => 'Entrada por traspaso (Planta Nueva)',
                    'after' => $statusEntry->toArray()
                ])
            ]);

            $asistenciaPrevia = WeeklyAssistence::where([
                'employee_id' => $employee_id,
                'week_number' => $week,
                'week_year'   => $year,
            ])->first();

            $beforeAssistence = $asistenciaPrevia ? $asistenciaPrevia->toArray() : null;

            $asistenciaActualizada = WeeklyAssistence::updateOrCreate(
                [
                    'employee_id'   => $employee_id,
                    'week_number'   => $week,
                    'week_year'     => $year,
                ],
                [
                    'branch_office_id'   => $branch_office_id
                ]
            );

            Logs::create([
                'action'          => $beforeAssistence ? 'UPDATE' : 'CREATE',
                'user_id'         => $userId,
                'table_name'      => 'weekly_assistences',
                'date'            => now(),
                'relationship_id' => $employee_id,
                'old_data'        => json_encode([
                    'info'   => 'Cambio de sucursal en asistencia semanal',
                    'before' => $beforeAssistence,
                    'after'  => $asistenciaActualizada->toArray()
                ])
            ]);


            DB::commit();
            return redirect()->back()->with('success', 'Traspaso procesado correctamente');

        } catch (\Throwable $e) {

            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error: ' . $e->getMessage()]);
        }
    }

    public function termination(Request $request){
        DB::beginTransaction();

        try {
            $type               = $request->type;
            $branch_office_id   = $request->branch_office_id;
            $duration_days      = $request->duration_days;
            $employee_id        = $request->employee_id;
            $reason_id          = $request->reason_id;
            $termination_date   = $request->termination_date;
            $entry_date         = $request->entry_date;
            $content            = $request->observations;
            $date               = Carbon::parse($entry_date);
            $year               = $date->year;
            $week               = $date->isoWeek;
            $userId             = auth()->id();

            $employee = Employee::find($employee_id);

            $before = $employee->toArray();

            if ($type === 'delete') {
                Logs::create([
                    'action'          => 'DELETE',
                    'user_id'         => $userId,
                    'table_name'      => 'employees',
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode(['before' => $before, 'after' => null])
                ]);

                $employee->delete();
            } else {
                $newStatus = EmployeeStatuse::create([
                    'employee_id'       => $employee_id,
                    'status'            => 'termination',
                    'content'           => $content,
                    'user_id'           => $userId,
                    'date'              => $termination_date,
                    'reason_id'         => $request->reason_id,
                    'branch_office_id'  => $employee->branch_office_id,
                    'values'            => json_encode(['Observaciones' => $request->observations])
                ]);

                $employee->update([
                    'status'           => 'termination',
                    'rehireable'       => $request->rehireable,
                    'termination_date' => $termination_date,
                ]);

                Logs::create([
                    'action'          => 'UPDATE',
                    'user_id'         => $userId,
                    'table_name'      => 'employees',
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode([
                        'before' => [
                            'status' => $before['status'],
                            'rehireable' => $before['rehireable'],
                            'termination_date' => $before['termination_date']
                        ],
                        'after' => $employee->getChanges()
                    ])
                ]);

                Logs::create([
                    'action'          => 'CREATE',
                    'user_id'         => $userId,
                    'table_name'      => 'employee_statuses',
                    'date'            => now(),
                    'relationship_id' => $employee->id,
                    'old_data'        => json_encode(['before' => null, 'after' => $newStatus->toArray()])
                ]);
            }

            EmployeeDayVacation::where('employee_id', $employee_id)->delete();
            WeeklyAssistence::where('employee_id', $employee_id)->delete();
            EmployeeCompensation::where('employee_id', $employee_id)->delete();
            EmployeeOvertime::where('employee_id', $employee_id)->delete();
            EmployeeShiftRole::where('employee_id', $employee_id)->delete();
            EmployeeSalaryPayment::where('employee_id', $employee_id)->delete();
            PayrollInvoice::where('employee_id', $employee_id)->delete();
            PayrollExtra::where('employee_id', $employee_id)->delete();

            $menssage = "";
            if ($type === 'delete') {
                $menssage = "Eliminado correctamente";
            }
            else{
                $menssage = "Baja procesada correctamente";
            }

            DB::commit();
            return redirect()->back()->with('success', 'Proceso correctamente');

        } catch (\Throwable $e) {

            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error: ' . $e->getMessage()]);

        }
    }


    public function import(Request $request)
    {
        $request->validate([
            // 'employees_csv' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            'employees_csv' => 'required|file|max:10240'
        ]);

        DB::beginTransaction();

        try{

            $file           = $request->file('employees_csv');
            $rows           = Excel::toArray([], $file);
            $sheet          = $rows[0];
            $headers        = $sheet[0];
            $data           = [];
            $filePdf        = $request->file('file_pdf');
            $excelName      = $file->getClientOriginalName();
            $pdfName        = $filePdf?->getClientOriginalName();

            $disk           = Storage::disk('remote_sftp');
            $dir            = 'employees';
            $disk->makeDirectory($dir);

            $excelRemotePath = $dir.'/'.$excelName;

            $disk->put(
                $excelRemotePath,
                file_get_contents($file->getRealPath())
            );

            $pdfRemotePath = null;

            if ($filePdf) {

                $pdfRemotePath = $dir.'/'.$pdfName;

                $disk->put(
                    $pdfRemotePath,
                    file_get_contents($filePdf->getRealPath())
                );
            }

            $insertados = 0;
            $fallidos = 0;
            $errores = [];
            $filaNumero = 1;

            $insertadosLista = [];
            $fallidosLista = [];

            foreach (array_slice($sheet, 1) as $row) {

                $filaNumero++;

                try {

                    if (!array_filter($row, fn($value) => trim((string)$value) !== '')) {
                        continue;
                    }

                    $row = array_pad($row, count($headers), null);
                    $row = array_combine($headers, $row);

                    $clave = trim((string)$row['Clave']);
                    $rfc   = trim((string)($row['RFC'] ?? ''));
                    $curp  = trim((string)($row['CURP'] ?? ''));
                    $nss   = trim((string)($row['IMSS'] ?? ''));

                    $existingEmployee = Employee::find($clave);

                    if (!$existingEmployee && !empty($curp)) {
                        $existingEmployee = Employee::where('dni', $curp)->first();
                    }

                    if (!$existingEmployee && !empty($nss)) {
                        $existingEmployee = Employee::where('health_id', $nss)->first();
                    }

                    $taxExists = TaxData::where('tax_id', $rfc)->exists();

                    if ($existingEmployee || $taxExists) {
                        $motivo = "";

                        if ($existingEmployee) {
                            $nombreEmp = "{$existingEmployee->name} {$existingEmployee->surname}";

                            if ((string)$existingEmployee->id === $clave) {
                                $motivo .= "El número de nómina '{$clave}' ya pertenece a {$nombreEmp}. ";
                            }
                            elseif (!empty($curp) && $existingEmployee->dni === $curp) {
                                $motivo .= "El CURP '{$curp}' ya está registrado para {$nombreEmp} (Nómina: {$existingEmployee->id}). ";
                            }
                            elseif (!empty($nss) && $existingEmployee->health_id === $nss) {
                                $motivo .= "El NSS '{$nss}' ya está registrado para {$nombreEmp} (Nómina: {$existingEmployee->id}). ";
                            }
                        }

                        if ($taxExists) {
                            $motivo .= "El RFC '{$rfc}' ya se encuentra registrado en el sistema.";
                        }

                        throw new \Exception(trim($motivo));
                    }


                    $state = State::where('name', 'like', '%' . $row['Estado'] . '%')->first();
                    $city           = City::where('name', $row['Ciudad'])->first();

                    $location = Location::firstOrCreate(
                        ['name' => trim($row['Colonia'])],
                        [
                            'city_id' => $city->id
                        ]
                    );

                    $bank = Bank::where('name', 'like', '%' . $row['Nombre del banco'] . '%')->first();
                    $paymentMethod  = PaymentMethod::where('name', $row['Forma de pago'])->first();
                    $position       = Position::where('name', $row['Puesto'])->first();
                    $departamet     = Departments::where('name', $row['Departamento'])->first();
                    $branchOffice   = BranchOffice::where('code', $row['Empresa'])->first();
                    $gender         = Gender::where('name', $row['Genero'])->first();
                    $shiftRoleModel = ShiftRole::where('name', $row['Rol de turno'])->first();

                    if (!$shiftRoleModel) {
                        throw new \Exception("Rol de turno no encontrado: ".$row['Rol de turno']);
                    }

                    if(!$state){
                        throw new \Exception("Estado no encontrado: ".$row['Estado']);
                    }

                    if(!$city){
                        throw new \Exception("Ciudad no encontrada: ".$row['Ciudad']);
                    }

                    if(!$bank){
                        throw new \Exception("Banco no encontrado: ".$row['Nombre del banco']);
                    }

                    if(!$paymentMethod){
                        throw new \Exception("Forma de pago no encontrada: ".$row['Forma de pago']);
                    }

                    if(!$position){
                        throw new \Exception("Puesto no encontrado: ".$row['Puesto']);
                    }

                    if(!$gender){
                        throw new \Exception("Genero no encontrado: ".$row['Genero']);
                    }

                    if(!$branchOffice){
                        throw new \Exception("Empresa no encontrada: ".$row['Empresa']);
                    }

                    if(!$departamet){
                        throw new \Exception("Departamento no encontrado: ".$row['Departamento']);
                    }

                    $birthday = null;
                    if (!empty($row['Fecha de nacimiento'])) {
                        $birthday = Carbon::createFromFormat('d/m/Y', $row['Fecha de nacimiento'])->format('Y-m-d');
                    }

                    $entryDate = null;
                    if (!empty($row['Fecha de ingreso'])) {
                        $entryDate = Carbon::createFromFormat('d/m/Y', $row['Fecha de ingreso'])->format('Y-m-d');
                    }

                    $additional_info = [
                        "profession"=>$row['Profesion'],
                        "level_education"=> $row['Nivel de estudios'],
                        "civil_state"=>$row['EstadoCivil'],
                        "beneficiary_name"=>$row['NombreBeneficiario'],
                        "days_duration"=>$row['Dias de duracion del periodo de prueba'],
                        "beneficiary_kinship"=>$row['Parentesco'],
                    ];

                    $employeeExist = Employee::where('id', $row['Clave'])->first();

                    if ($employeeExist) {

                        $fallidos++;

                        $fallidosLista[] = [
                            'id' => $employeeExist->id,
                            'nombre' => $employeeExist->name,
                            'apellido_paterno' => $employeeExist->surname,
                            'apellido_materno' => $employeeExist->mother_surname,
                            'error' => 'El empleado ya existe'
                        ];

                        continue;
                    }

                    try {

                        $employee = Employee::create([
                            'id'                    => $row['Clave'],
                            'code'                  => $row['Clave'],
                            'external_id'           => null,
                            'name'                  => $row['Nombres'],
                            'email'                 => $row['Correo electronico'],
                            'surname'               => $row['Apellido paterno'],
                            'mother_surname'        => $row['Apellido materno'],
                            'personal_phone'        => $row['Telefono'],
                            'company_phone'         => $row['Telefono empresa'],
                            'employee_parent_id'    => $row['Jefe Inmediato'],
                            'status'                => 'entry',
                            'dni'                   => $row['CURP'],
                            'health_id'             => $row['IMSS'],
                            'department_id'         => $departamet->id,
                            'position_id'           => $position->id,
                            'gender_id'             => $gender->id,
                            'branch_office_id'      => $branchOffice->id,
                            'birthday'              => $birthday,
                            'entry_date'            => $entryDate,
                            'rehireable'            => 1,
                            'additional_info'       => $additional_info
                        ]);

                        $Logs = Logs::create([
                            'action'          => 'INSERT',
                            'user_id'         => auth()->id(),
                            'table_name'      => 'employees',
                            'date'            => Carbon::now('America/Mexico_City'),
                            'old_data'        => null,
                            'relationship_id' => $employee->id,
                            'new_data'        => json_encode($employee->getAttributes())

                        ]);

                    } catch (\Throwable $e) {

                        throw new \Exception(
                            "Error al insertar empleado CLAVE {$row['Clave']} - ".$e->getMessage()
                        );

                    }

                    $shiftRole = EmployeeShiftRole::create([
                        'employee_id'      => $employee->id,
                        'shift_role_id'    => $shiftRoleModel->id,
                        'start_date'       => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                        'end_date'         => null,
                        'active'           => 1,
                        'branch_office_id' => $branchOffice->id
                    ]);

                    Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'employee_shift_roles',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'relationship_id' => $shiftRole->id,
                        'new_data'        => json_encode($shiftRole->getAttributes())
                    ]);

                    $shiftRoleCycle = ShiftRoleCycle::create([
                        'employee_id'   => $employee->id,
                        'shift_role_id' => $shiftRoleModel->id,
                        'date'          => Carbon::now('America/Mexico_City')->format('Y-m-d'),
                        'started_at'    => Carbon::now('America/Mexico_City'),
                        'ends_at'       => null
                    ]);

                    Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'employee_shift_role_cycles',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'relationship_id' => $shiftRoleCycle->id,
                        'new_data'        => json_encode($shiftRoleCycle->getAttributes())
                    ]);

                    $Address = Address::create([
                        'street'           => $row['Calle'],
                        'external_number'  => null,
                        'internal_number'  => null,
                        'location_id'      => $location->id,
                        'city_id'          => $city->id,
                        'state_id'         => $state->id,
                        'country_id'       => 1,
                        'postal_code'      => $row['Codigo postal'],
                        'addressable_type' => Employee::class,
                        'addressable_id'   => $employee->id,
                    ]);

                    $Logs = Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'address',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'old_data'        => null,
                        'relationship_id' => $Address->id,
                        'new_data'        => json_encode($Address->getAttributes())
                    ]);

                    if(!$Address){
                        throw new \Exception("No se pudo insertar Address");
                    }

                    $TaxData = TaxData::create([
                        'tax_id'        => $row['RFC'],
                        'postal_code'   => $row['Codigo postal fiscal'],
                        'email'         => null,
                        'tax_system_id' => null,
                        'owner_type'    => Employee::class,
                        'owner_id'      => $employee->id,
                    ]);

                    $Logs = Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'taxData',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'old_data'        => null,
                        'relationship_id' => $TaxData->id,
                        'new_data'        => json_encode($TaxData->getAttributes())
                    ]);

                    if(!$TaxData){
                        throw new \Exception("No se pudo insertar TaxData");
                    }

                    $formatNumber = function($value) {
                        if (is_null($value)) return null;
                        $value = trim((string)$value);
                        if (str_contains(strtoupper($value), 'E+')) {
                            return number_format((float)$value, 0, '', '');
                        }
                        return $value;
                    };

                    $Payment = PaymentData::create([
                        'account_number'      => $formatNumber($row['Cta. de cheques']),
                        'account_card'        => $formatNumber($row['Numero de Tarjeta']),
                        'account_code'        => $formatNumber($row['Clave Interbancaria']),
                        'salary'              => $row['Salario Semanal'],
                        'daily_salary'        => null,
                        'meta'                => null,
                        'bank_id'             => $bank->id,
                        'salary_type_id'      => null,
                        'payment_method_id'   => $paymentMethod->id,
                        'external_account_id' => null,
                        'owner_id'            => $employee->id,
                        'owner_type'          => Employee::class
                    ]);

                    $Logs = Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'paymentData',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'old_data'        => null,
                        'relationship_id' => $Payment->id,
                        'new_data'        => json_encode($Payment->getAttributes())
                    ]);

                    if(!$Payment){
                        throw new \Exception("No se pudo insertar Payment");
                    }

                    $benefits = explode(',', $row['Prestaciones']);

                    foreach ($benefits as $benefitName) {

                        $benefitName = trim($benefitName);

                        $benefit = Benefit::where('name', $benefitName)->first();

                        if ($benefit) {
                            $Benefit = BenefitEmployee::create([
                                'employee_id' => $employee->id,
                                'benefit_id'  => $benefit->id
                            ]);
                        }
                    }

                    $Logs = Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'benefits',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'old_data'        => null,
                        'relationship_id' => $Benefit->id,
                        'new_data'        => json_encode($row)
                    ]);

                    if(!$Logs){
                        throw new \Exception("No se pudo insertar Logs");
                    }

                    $fullName = trim("{$row['Nombres']} {$row['Apellido paterno']} {$row['Apellido materno']}");

                    $quitarAcentos = function($cadena) {
                        $buscar  = ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'];
                        $reemplazar = ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'];
                        return str_replace($buscar, $reemplazar, $cadena);
                    };

                    $firstName     = Str::upper($quitarAcentos(explode(' ', trim($row['Nombres']))[0]));
                    $firstSurname  = Str::upper($quitarAcentos(trim($row['Apellido paterno'])));
                    $secondSurname = Str::upper($quitarAcentos(trim($row['Apellido materno'])));

                    $username = "{$firstName}.{$firstSurname}";

                    if (User::where('username', $username)->exists()) {
                        $username = "{$firstName}.{$secondSurname}";
                        if (User::where('username', $username)->exists()) {
                            $username = "{$firstName}_{$firstSurname}";
                            if (User::where('username', $username)->exists()) {
                                $username = $username . $row['Clave'];
                            }
                        }
                    }

                    $user = User::create([
                        'name'              => $fullName,
                        'username'          => $username,
                        'email'             => $row['Correo electronico'],
                        'password'          => Hash::make($row['Clave']),
                        'current_branch_office_id' => $branchOffice->id,
                        'default_locale'    => 'es',
                        'email_verified_at' => now(),
                    ]);

                    $employee->update(['user_id' => $user->id]);

                    Logs::create([
                        'action'          => 'INSERT',
                        'user_id'         => auth()->id(),
                        'table_name'      => 'users',
                        'date'            => Carbon::now('America/Mexico_City'),
                        'relationship_id' => $user->id,
                        'new_data'        => json_encode($user->getAttributes())
                    ]);

                    $additionalInfo = $employee->additional_info ?? [];

                    if (is_string($additionalInfo)) {
                        $additionalInfo = json_decode($additionalInfo, true);
                    }

                    $additionalInfo['user_id'] = $user->id;

                    $employee->update([
                        'additional_info' => $additionalInfo
                    ]);

                    $insertados++;

                    $insertadosLista[] = [
                        'id' => $employee->id,
                        'nombre' => $employee->name,
                        'apellido_paterno' => $employee->surname,
                        'apellido_materno' => $employee->mother_surname
                    ];

                } catch (\Throwable $e) {

                    $fallidos++;

                    $fallidosLista[] = [
                        'id' => $row['Clave'] ?? null,
                        'nombre' => $row['Nombres'] ?? null,
                        'apellido_paterno' => $row['Apellido paterno'] ?? null,
                        'apellido_materno' => $row['Apellido materno'] ?? null,
                        'error' => $e->getMessage()
                    ];

                    $errores[] = [
                        'fila' => $filaNumero,
                        'empleado' => $row['Nombres'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'insertados' => $insertados,
                'fallidos' => $fallidos,
                'insertados_lista' => $insertadosLista,
                'fallidos_lista' => $fallidosLista,
                'errores' => $errores
            ]);

        }catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar los empleados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function massive_edition()
    {
        return Inertia::render('EmployeeCatalog/MassiveEdition');
    }

    public function massiveUpdate(Request $request)
    {
        $datos = $request->datos;
        $insertados = 0;
        $fallidos = 0;
        $insertadosLista = [];
        $fallidosLista = [];

        DB::beginTransaction();

        try {
            $estructura = [
                'employees' => [
                    'name','surname','mother_surname','email','personal_phone',
                    'company_phone','status','dni','health_id',
                    'department_id','position_id','gender_id',
                    'birthday','entry_date','termination_date',
                    'branch_office_id','employee_parent_id'
                ],
                'payment_data' => [
                    'salary','daily_salary','bank_id','payment_method_id',
                    'account_number','account_card','account_code'
                ],
                'addresses' => [
                    'street','external_number','internal_number',
                    'location_id','city_id','state_id','postal_code'
                ],
                'tax_data' => [
                    'tax_id','tax_system_id','postal_code'
                ]
            ];

            $camposAdditionalInfo = [
                "user_id", "additional_phone", "additional_email", "profession",
                "level_education", "civil_state", "unit_health", "emergency_name",
                "emergency_phone", "employee_type", "job_type", "employer_registration",
                "medical_unit", "pension_discount", "clasification", "tipo_ingresos",
                "clave_regimen", "base_cotizacion", "tipo_contrato", "ruta_plantilla",
                "clave_regimen_cntrato", "tabla_vacaciones", "tabla_salario_diario",
                "days_duration", "beneficiary_name", "porcentaje_beneficiario",
                "beneficiary_kinship", "nombre_beneficiario2", "porcentaje_beneficiario2",
                "parentesco_beneficiario2", "external_id"
            ];

            foreach ($datos as $index => $fila) {
                $resumenCambios = [];

                try {
                    // 1. MAPEO DE EXCEL A BASE DE DATOS
                    if (isset($fila['Clave'])) $fila['code'] = $fila['Clave'];
                    if (isset($fila['Nombres'])) $fila['name'] = $fila['Nombres'];
                    if (isset($fila['Apellido paterno'])) $fila['surname'] = $fila['Apellido paterno'];
                    if (isset($fila['Apellido materno'])) $fila['mother_surname'] = $fila['Apellido materno'];
                    if (isset($fila['Correo electronico'])) $fila['email'] = $fila['Correo electronico'];
                    if (isset($fila['Telefono'])) $fila['personal_phone'] = $fila['Telefono'];
                    if (isset($fila['RFC'])) $fila['dni'] = $fila['RFC'];
                    if (isset($fila['IMSS'])) $fila['health_id'] = $fila['IMSS'];
                    if (isset($fila['Departamento'])) $fila['department_id'] = $fila['Departamento'];
                    if (isset($fila['Puesto'])) $fila['position_id'] = $fila['Puesto'];
                    if (isset($fila['Genero'])) $fila['gender_id'] = $fila['Genero'];
                    if (isset($fila['Fecha de nacimiento'])) $fila['birthday'] = $fila['Fecha de nacimiento'];
                    if (isset($fila['Fecha de ingreso'])) $fila['entry_date'] = $fila['Fecha de ingreso'];
                    if (isset($fila['Calle'])) $fila['street'] = $fila['Calle'];
                    if (isset($fila['Colonia'])) $fila['location_id'] = $fila['Colonia'];
                    if (isset($fila['Ciudad'])) $fila['city_id'] = $fila['Ciudad'];
                    if (isset($fila['Estado'])) $fila['state_id'] = $fila['Estado'];
                    if (isset($fila['Codigo postal'])) $fila['employees_postal_code'] = $fila['Codigo postal'];
                    if (isset($fila['Codigo postal fiscal'])) $fila['tax_postal_code'] = $fila['Codigo postal fiscal'];
                    if (isset($fila['Salario diario'])) $fila['daily_salary'] = $fila['Salario diario'];
                    if (isset($fila['Nombre del banco'])) $fila['bank_id'] = $fila['Nombre del banco'];
                    if (isset($fila['Forma de pago'])) $fila['payment_method_id'] = $fila['Forma de pago'];
                    if (isset($fila['Cta. de cheques'])) $fila['account_number'] = $fila['Cta. de cheques'];
                    if (isset($fila['Numero de Tarjeta'])) $fila['account_card'] = $fila['Numero de Tarjeta'];
                    if (isset($fila['Clave Interbancaria'])) $fila['account_code'] = $fila['Clave Interbancaria'];
                    if (isset($fila['Rol de turno'])) $fila['shift_role_id'] = $fila['Rol de turno'];
                    if (isset($fila['Inicio rol de turno'])) $fila['start_date'] = $fila['Inicio rol de turno'];
                    if (isset($fila['Prestaciones'])) $fila['benefits'] = $fila['Prestaciones'];

                    // MAPEO PARA ADDITIONAL_INFO (JSON)
                    if (isset($fila['Nivel de estudios'])) $fila['level_education'] = $fila['Nivel de estudios'];
                    if (isset($fila['Profesion'])) $fila['profession'] = $fila['Profesion'];
                    if (isset($fila['EstadoCivil'])) $fila['civil_state'] = $fila['EstadoCivil'];
                    if (isset($fila['Registro patronal'])) $fila['employer_registration'] = $fila['Registro patronal'];
                    if (isset($fila['Tipo Empleado'])) $fila['employee_type'] = $fila['Tipo Empleado'];
                    if (isset($fila['NombreBeneficiario'])) $fila['beneficiary_name'] = $fila['NombreBeneficiario'];
                    if (isset($fila['Parentesco'])) $fila['beneficiary_kinship'] = $fila['Parentesco'];
                    if (isset($fila['Dias de duracion del periodo de prueba'])) $fila['days_duration'] = $fila['Dias de duracion del periodo de prueba'];

                    $code = $fila['code'] ?? null;
                    if (!$code) throw new \Exception("No se encontró 'code' o 'Clave'");

                    $employee = Employee::where('code', $code)->first();
                    if (!$employee) throw new \Exception("Empleado {$code} no existe");

                    $formatDate = function($dateStr) {
                        if (!$dateStr || strlen(trim($dateStr)) < 8) return null;
                        try {
                            return Carbon::createFromFormat('d/m/Y', trim($dateStr))->format('Y-m-d');
                        } catch (\Exception $e) { return null; }
                    };

                    if (isset($fila['birthday'])) $fila['birthday'] = $formatDate($fila['birthday']);
                    if (isset($fila['entry_date'])) $fila['entry_date'] = $formatDate($fila['entry_date']);
                    if (isset($fila['start_date'])) $fila['start_date'] = $formatDate($fila['start_date']);

                    // Relaciones
                    if (isset($fila['shift_role_id']) && !empty(trim($fila['shift_role_id']))) {
                        $shift = ShiftRole::where('name', 'LIKE', '%' . trim($fila['shift_role_id']) . '%')->first();
                        $fila['shift_role_id'] = $shift ? $shift->id : null;
                    }
                    if (isset($fila['department_id']) && !empty(trim($fila['department_id']))) {
                        $dept = Departments::where('name', 'LIKE', '%' . trim($fila['department_id']) . '%')->first();
                        $fila['department_id'] = $dept ? $dept->id : null;
                    }
                    if (isset($fila['position_id']) && !empty(trim($fila['position_id']))) {
                        $pos = Position::where('name', 'LIKE', '%' . trim($fila['position_id']) . '%')->first();
                        $fila['position_id'] = $pos ? $pos->id : null;
                    }

                    $updatesPorTabla = [];
                    $benefitsToSync = null;
                    $newShiftRoleId = null;
                    $startDate = null;

                    $additionalInfo = is_array($employee->additional_info)
                        ? $employee->additional_info
                        : json_decode($employee->additional_info ?? '[]', true);

                    foreach ($fila as $campoBd => $valorOriginal) {
                        if ($campoBd === 'code' || $campoBd === 'Clave') continue;

                        $valor = trim((string)$valorOriginal);
                        $valor = ($valor === '') ? null : $valor;

                        if ($campoBd === 'benefits') {
                            $benefitsToSync = array_map('trim', explode(',', $valor));
                            continue;
                        }
                        if ($campoBd === 'shift_role_id') {
                            $newShiftRoleId = $valor;
                            continue;
                        }
                        if ($campoBd === 'start_date') {
                            $startDate = $valor;
                            continue;
                        }
                        if ($campoBd === 'employees_postal_code') {
                            $updatesPorTabla['addresses']['postal_code'] = $valor;
                            continue;
                        }
                        if ($campoBd === 'tax_postal_code') {
                            $updatesPorTabla['tax_data']['postal_code'] = $valor;
                            continue;
                        }
                        if (in_array($campoBd, $camposAdditionalInfo)) {
                            $additionalInfo[$campoBd] = $valor;
                            continue;
                        }

                        foreach ($estructura as $tabla => $campos) {
                            if (in_array($campoBd, $campos)) {
                                $updatesPorTabla[$tabla][$campoBd] = $valor;
                                break;
                            }
                        }
                    }

                    // 2. LOGICA ADDITIONAL INFO
                    $oldAdditional = is_array($employee->additional_info) ? $employee->additional_info : json_decode($employee->additional_info ?? '[]', true);
                    if (json_encode($oldAdditional) !== json_encode($additionalInfo)) {
                        foreach ($additionalInfo as $key => $value) {
                            $valorViejo = $oldAdditional[$key] ?? null;
                            if ((string)$valorViejo !== (string)$value) {
                                $resumenCambios['employees']['before'][$key] = $valorViejo;
                                $resumenCambios['employees']['after'][$key] = $value;
                            }
                        }
                        $updatesPorTabla['employees']['additional_info'] = $additionalInfo;
                    }

                    // 3. UPDATES TABLAS DINAMICAS
                    foreach ($updatesPorTabla as $tabla => $data) {
                        if (empty($data)) continue;
                        $model = null;
                        switch ($tabla) {
                            case 'employees': $model = $employee; break;
                            case 'payment_data': $model = PaymentData::firstOrNew(['owner_id' => $employee->id, 'owner_type' => Employee::class]); break;
                            case 'addresses': $model = Address::firstOrNew(['addressable_id' => $employee->id, 'addressable_type' => Employee::class]); break;
                            case 'tax_data': $model = TaxData::firstOrNew(['owner_id' => $employee->id, 'owner_type' => Employee::class]); break;
                        }

                        if ($model) {
                            $oldAttributes = $model->getAttributes();
                            $model->fill($data);
                            $dirty = $model->getDirty();

                            if (!empty($dirty)) {
                                $before = array_intersect_key($oldAttributes, $dirty);
                                $model->save();
                                $changes = $model->getChanges();

                                if (!isset($resumenCambios[$tabla])) {
                                    $resumenCambios[$tabla] = ['before' => [], 'after' => []];
                                }

                                $resumenCambios[$tabla]['before'] = array_merge($resumenCambios[$tabla]['before'], $before);
                                $resumenCambios[$tabla]['after'] = array_merge($resumenCambios[$tabla]['after'], $changes);

                                unset($resumenCambios[$tabla]['before']['additional_info']);
                                unset($resumenCambios[$tabla]['after']['additional_info']);

                                Logs::create([
                                    'action' => 'UPDATE',
                                    'user_id' => auth()->id(),
                                    'table_name' => $model->getTable(),
                                    'date' => now(),
                                    'relationship_id' => $employee->id,
                                    'old_data' => json_encode(['before' => $before, 'after' => $changes])
                                ]);
                            }
                        }
                    }

                    // 4. SHIFT + LOG
                    if ($newShiftRoleId || $startDate) {
                        $currentShift = EmployeeShiftRole::where('employee_id', $employee->id)->where('active', 1)->first();
                        $finalShiftRoleId = $newShiftRoleId ?: ($currentShift ? $currentShift->shift_role_id : null);

                        if ($finalShiftRoleId) {
                            $cambioId = $newShiftRoleId && (!$currentShift || $currentShift->shift_role_id != $newShiftRoleId);
                            $cambioFecha = $startDate && (!$currentShift || $currentShift->start_date != $startDate);

                            if ($cambioId || $cambioFecha) {
                                $oldS = $currentShift ? $currentShift->getAttributes() : null;
                                if ($currentShift) $currentShift->update(['active' => 0, 'end_date' => now()->format('Y-m-d')]);

                                $newS = EmployeeShiftRole::create([
                                    'employee_id' => $employee->id,
                                    'shift_role_id' => $finalShiftRoleId,
                                    'start_date' => $startDate ?? now()->format('Y-m-d'),
                                    'active' => 1,
                                    'branch_office_id' => $employee->branch_office_id
                                ]);

                                $resumenCambios['shift'] = ['before' => $oldS, 'after' => $newS->getAttributes()];

                                // LOG SHIFT
                                Logs::create([
                                    'action' => 'UPDATE',
                                    'user_id' => auth()->id(),
                                    'table_name' => 'employee_shift_roles',
                                    'date' => now(),
                                    'relationship_id' => $employee->id,
                                    'old_data' => json_encode($resumenCambios['shift'])
                                ]);
                            }
                        }
                    }

                    // 5. BENEFITS + LOG
                    if ($benefitsToSync !== null) {
                        $oldBenefits = $employee->benefits()->pluck('benefits.id')->toArray();
                        $benefitIds = Benefit::whereIn('name', $benefitsToSync)->pluck('id')->toArray();
                        sort($oldBenefits); sort($benefitIds);

                        if ($oldBenefits !== $benefitIds) {
                            $employee->benefits()->sync($benefitIds);
                            $resumenCambios['benefits'] = ['before' => $oldBenefits, 'after' => $benefitIds];

                            // LOG BENEFITS
                            Logs::create([
                                'action' => 'UPDATE',
                                'user_id' => auth()->id(),
                                'table_name' => 'employee_benefits',
                                'date' => now(),
                                'relationship_id' => $employee->id,
                                'old_data' => json_encode($resumenCambios['benefits'])
                            ]);
                        }
                    }

                    $insertados++;
                    $insertadosLista[] = [
                        'id' => $employee->id,
                        'nombre' => trim("{$employee->name} {$employee->surname} {$employee->mother_surname}"),
                        'cambios' => $resumenCambios
                    ];

                } catch (\Throwable $e) {
                    $fallidos++;
                    $fallidosLista[] = ['id' => $fila['code'] ?? $fila['Clave'] ?? 'N/A', 'motivo' => $e->getMessage()];
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'actualizados' => $insertados,
                'fallidos' => $fallidos,
                'insertados_lista' => $insertadosLista,
                'fallidos_lista' => $fallidosLista
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // public function massiveUpdate(Request $request)
    // {
    //     $datos = $request->datos;
    //     $insertados = 0;
    //     $fallidos = 0;
    //     $insertadosLista = [];
    //     $fallidosLista = [];

    //     DB::beginTransaction();

    //     try {
    //         $estructura = [
    //             'employees' => [
    //                 'name','surname','mother_surname','email','personal_phone',
    //                 'company_phone','status','dni','health_id',
    //                 'department_id','position_id','gender_id',
    //                 'birthday','entry_date','termination_date',
    //                 'branch_office_id','employee_parent_id'
    //             ],
    //             'payment_data' => [
    //                 'salary','daily_salary','bank_id','payment_method_id',
    //                 'account_number','account_card','account_code'
    //             ],
    //             'addresses' => [
    //                 'street','external_number','internal_number',
    //                 'location_id','city_id','state_id','postal_code'
    //             ],
    //             'tax_data' => [
    //                 'tax_id','tax_system_id','postal_code'
    //             ]
    //         ];

    //         $camposAdditionalInfo = [
    //             "user_id",
    //             "additional_phone",
    //             "additional_email",
    //             "profession",
    //             "level_education",
    //             "civil_state",
    //             "unit_health",
    //             "emergency_name",
    //             "emergency_phone",
    //             "employee_type",
    //             "job_type",
    //             "employer_registration",
    //             "medical_unit",
    //             "pension_discount",
    //             "clasification",
    //             "tipo_ingresos",
    //             "clave_regimen",
    //             "base_cotizacion",
    //             "tipo_contrato",
    //             "ruta_plantilla",
    //             "clave_regimen_cntrato",
    //             "tabla_vacaciones",
    //             "tabla_salario_diario",
    //             "days_duration",
    //             "beneficiary_name",
    //             "porcentaje_beneficiario",
    //             "beneficiary_kinship",
    //             "nombre_beneficiario2",
    //             "porcentaje_beneficiario2",
    //             "parentesco_beneficiario2",
    //             "external_id"
    //         ];

    //         foreach ($datos as $index => $fila) {
    //             $resumenCambios = [];
    //             $filaNumero = $index + 2;

    //             try {
    //                 $code = $fila['code'] ?? null;
    //                 if (!$code) throw new \Exception("No se encontró 'code'");

    //                 $employee = Employee::where('code', $code)->first();
    //                 if (!$employee) throw new \Exception("Empleado {$code} no existe");

    //                 $formatDate = function($dateStr) {
    //                     if (!$dateStr || strlen(trim($dateStr)) < 8) return null;
    //                     try {
    //                         return Carbon::createFromFormat('d/m/Y', trim($dateStr))->format('Y-m-d');
    //                     } catch (\Exception $e) {
    //                         return null;
    //                     }
    //                 };

    //                 if (isset($fila['birthday'])) $fila['birthday'] = $formatDate($fila['birthday']);
    //                 if (isset($fila['entry_date'])) $fila['entry_date'] = $formatDate($fila['entry_date']);
    //                 if (isset($fila['start_date'])) $fila['start_date'] = $formatDate($fila['start_date']);

    //                 if (isset($fila['shift_role_id']) && !empty(trim($fila['shift_role_id']))) {
    //                     $shift = ShiftRole::where('name', 'LIKE', '%' . trim($fila['shift_role_id']) . '%')->first();
    //                     $fila['shift_role_id'] = $shift ? $shift->id : null;
    //                 }

    //                 if (isset($fila['department_id']) && !empty(trim($fila['department_id']))) {
    //                     $dept = Departments::where('name', 'LIKE', '%' . trim($fila['department_id']) . '%')->first();
    //                     $fila['department_id'] = $dept ? $dept->id : null;
    //                 }

    //                 if (isset($fila['position_id']) && !empty(trim($fila['position_id']))) {
    //                     $pos = Position::where('name', 'LIKE', '%' . trim($fila['position_id']) . '%')->first();
    //                     $fila['position_id'] = $pos ? $pos->id : null;
    //                 }

    //                 $updatesPorTabla = [];
    //                 $benefitsToSync = null;
    //                 $newShiftRoleId = null;
    //                 $startDate = null;

    //                 // Preparar Additional Info
    //                 $additionalInfo = is_array($employee->additional_info)
    //                     ? $employee->additional_info
    //                     : json_decode($employee->additional_info ?? '[]', true);

    //                 // =========================
    //                 // MAPEO DINÁMICO
    //                 // =========================
    //                 foreach ($fila as $campoBd => $valorOriginal) {
    //                     if ($campoBd === 'code') continue;

    //                     $valor = trim((string)$valorOriginal);
    //                     $valor = ($valor === '') ? null : $valor;

    //                     if ($campoBd === 'benefits') {
    //                         $benefitsToSync = array_map('trim', explode(',', $valor));
    //                         continue;
    //                     }
    //                     if ($campoBd === 'shift_role_id') {
    //                         $newShiftRoleId = $valor;
    //                         continue;
    //                     }
    //                     if ($campoBd === 'start_date') {
    //                         $startDate = $valor;
    //                         continue;
    //                     }
    //                     if ($campoBd === 'employees_postal_code') {
    //                         $updatesPorTabla['addresses']['postal_code'] = $valor;
    //                         continue;
    //                     }
    //                     if ($campoBd === 'tax_postal_code') {
    //                         $updatesPorTabla['tax_data']['postal_code'] = $valor;
    //                         continue;
    //                     }
    //                     if (in_array($campoBd, $camposAdditionalInfo)) {
    //                         $additionalInfo[$campoBd] = $valor;
    //                         continue;
    //                     }

    //                     foreach ($estructura as $tabla => $campos) {
    //                         if (in_array($campoBd, $campos)) {
    //                             $updatesPorTabla[$tabla][$campoBd] = $valor;
    //                             break;
    //                         }
    //                     }
    //                 }

    //                 // =========================
    //                 // 1. GUARDAR ADDITIONAL INFO
    //                 // =========================
    //                 $oldAdditional = is_array($employee->additional_info) ? $employee->additional_info : json_decode($employee->additional_info ?? '[]', true);

    //                 if (json_encode($oldAdditional) !== json_encode($additionalInfo)) {
    //                     foreach ($additionalInfo as $key => $value) {
    //                         $valorViejo = $oldAdditional[$key] ?? null;
    //                         // Comparamos valores para el resumen visual
    //                         if ((string)$valorViejo !== (string)$value) {
    //                             $resumenCambios['employees']['before'][$key] = $valorViejo;
    //                             $resumenCambios['employees']['after'][$key] = $value;
    //                         }
    //                     }
    //                     // Metemos el JSON a la tabla employees para que se guarde en el siguiente paso
    //                     $updatesPorTabla['employees']['additional_info'] = $additionalInfo;
    //                 }

    //                 // =========================
    //                 // 2. UPDATES DINÁMICOS (TABLAS)
    //                 // =========================
    //                 foreach ($updatesPorTabla as $tabla => $data) {
    //                     if (empty($data)) continue;

    //                     $model = null;
    //                     switch ($tabla) {
    //                         case 'employees':
    //                             $model = $employee;
    //                             break;
    //                         case 'payment_data':
    //                             $model = PaymentData::firstOrNew(['owner_id' => $employee->id, 'owner_type' => Employee::class]);
    //                             break;
    //                         case 'addresses':
    //                             $model = Address::firstOrNew(['addressable_id' => $employee->id, 'addressable_type' => Employee::class]);
    //                             break;
    //                         case 'tax_data':
    //                             $model = TaxData::firstOrNew(['owner_id' => $employee->id, 'owner_type' => Employee::class]);
    //                             break;
    //                     }

    //                     if ($model) {
    //                         $oldAttributes = $model->getAttributes();
    //                         $model->fill($data);
    //                         $dirty = $model->getDirty();

    //                         if (!empty($dirty)) {
    //                             // Capturamos los valores antes del cambio
    //                             $before = array_intersect_key($oldAttributes, $dirty);
    //                             $model->save();
    //                             $changes = $model->getChanges();

    //                             if (!isset($resumenCambios[$tabla])) {
    //                                 $resumenCambios[$tabla] = ['before' => [], 'after' => []];
    //                             }

    //                             // CORRECCIÓN: Usar array_merge para NO borrar lo que ya pusimos de additional_info
    //                             $resumenCambios[$tabla]['before'] = array_merge($resumenCambios[$tabla]['before'], $before);
    //                             $resumenCambios[$tabla]['after'] = array_merge($resumenCambios[$tabla]['after'], $changes);

    //                             // Limpieza para el Log (opcional, para no ver el JSON gigante)
    //                             unset($resumenCambios[$tabla]['before']['additional_info']);
    //                             unset($resumenCambios[$tabla]['after']['additional_info']);

    //                             // LOG DE LA TABLA
    //                             Logs::create([
    //                                 'action' => 'UPDATE',
    //                                 'user_id' => auth()->id(),
    //                                 'table_name' => $model->getTable(),
    //                                 'date' => now(),
    //                                 'relationship_id' => $employee->id,
    //                                 'old_data' => json_encode([
    //                                     'before' => $before,
    //                                     'after' => $changes
    //                                 ])
    //                             ]);
    //                         }
    //                     }
    //                 }

    //                 // =========================
    //                 // 3. SHIFT (ROL DE TURNO)
    //                 // =========================
    //                 if ($newShiftRoleId || $startDate) {
    //                     $currentShift = EmployeeShiftRole::where('employee_id', $employee->id)
    //                         ->where('active', 1)
    //                         ->first();

    //                     // Validamos qué ID usar
    //                     $finalShiftRoleId = $newShiftRoleId ?: ($currentShift ? $currentShift->shift_role_id : null);

    //                     if (!$finalShiftRoleId) {
    //                         throw new \Exception("El empleado no tiene un rol asignado actualmente. Por favor, asigne un 'shift_role_id' en el archivo.");
    //                     }

    //                     $cambioId = $newShiftRoleId && (!$currentShift || $currentShift->shift_role_id != $newShiftRoleId);
    //                     $cambioFecha = $startDate && (!$currentShift || $currentShift->start_date != $startDate);

    //                     if ($cambioId || $cambioFecha) {
    //                         $oldShift = $currentShift ? $currentShift->getAttributes() : null;

    //                         if ($currentShift) {
    //                             $currentShift->update([
    //                                 'active' => 0,
    //                                 'end_date' => now()->format('Y-m-d')
    //                             ]);
    //                         }

    //                         $newShift = EmployeeShiftRole::create([
    //                             'employee_id' => $employee->id,
    //                             'shift_role_id' => $finalShiftRoleId,
    //                             'start_date' => $startDate ?? now()->format('Y-m-d'),
    //                             'active' => 1,
    //                             'branch_office_id' => $employee->branch_office_id
    //                         ]);

    //                         $resumenCambios['shift'] = [
    //                             'before' => $oldShift,
    //                             'after' => $newShift->getAttributes()
    //                         ];

    //                         // LOG DE SHIFT
    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_shift_roles',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode($resumenCambios['shift'])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // 4. BENEFITS
    //                 // =========================
    //                 if ($benefitsToSync !== null) {
    //                     $oldBenefits = $employee->benefits()->pluck('benefits.id')->toArray();

    //                     $benefitIds = Benefit::whereIn('name', $benefitsToSync)->pluck('id')->toArray();

    //                     sort($oldBenefits);
    //                     sort($benefitIds);

    //                     if ($oldBenefits !== $benefitIds) {
    //                         $employee->benefits()->sync($benefitIds);

    //                         $resumenCambios['benefits'] = [
    //                             'before' => $oldBenefits,
    //                             'after' => $benefitIds
    //                         ];

    //                         // LOG DE BENEFITS
    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_benefits',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode($resumenCambios['benefits'])
    //                         ]);
    //                     }
    //                 }

    //                 $insertados++;
    //                 $insertadosLista[] = [
    //                     'id' => $employee->id,
    //                     'nombre' => trim("{$employee->name} {$employee->surname} {$employee->mother_surname}"),
    //                     'cambios' => $resumenCambios
    //                 ];

    //             } catch (\Throwable $e) {
    //                 $fallidos++;
    //                 $fallidosLista[] = ['id' => $fila['code'] ?? 'N/A', 'motivo' => $e->getMessage()];
    //             }
    //         }

    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'actualizados' => $insertados,
    //             'fallidos' => $fallidos,
    //             'insertados_lista' => $insertadosLista,
    //             'fallidos_lista' => $fallidosLista
    //         ]);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // public function massiveUpdate(Request $request)
    // {
    //     $datos = $request->datos;

    //     $insertados = 0;
    //     $fallidos = 0;
    //     $insertadosLista = [];
    //     $fallidosLista = [];


    //     DB::beginTransaction();

    //     try {

    //         $estructura = [
    //             'employees' => [
    //                 'name','surname','mother_surname','email','personal_phone',
    //                 'company_phone','status','dni','health_id',
    //                 'department_id','position_id','gender_id',
    //                 'birthday','entry_date','termination_date',
    //                 'branch_office_id','employee_parent_id'
    //             ],
    //             'payment_data' => [
    //                 'salary','daily_salary','bank_id','payment_method_id',
    //                 'account_number','account_card','account_code'
    //             ],
    //             'addresses' => [
    //                 'street','external_number','internal_number',
    //                 'location_id','city_id','state_id','postal_code'
    //             ],
    //             'tax_data' => [
    //                 'tax_id','tax_system_id','postal_code'
    //             ]
    //         ];

    //         $camposAdditionalInfo = [
    //             "user_id","additional_phone","additional_email","profession",
    //             "level_education","civil_state","unit_health","emergency_name",
    //             "emergency_phone","employee_type","job_type",
    //             "employer_registration","medical_unit","pension_discount",
    //             "clasification","tipo_ingresos","clave_regimen",
    //             "base_cotizacion","tipo_contrato","ruta_plantilla",
    //             "clave_regimen_cntrato","tabla_vacaciones",
    //             "tabla_salario_diario","days_duration","beneficiary_name",
    //             "porcentaje_beneficiario","beneficiary_kinship",
    //             "nombre_beneficiario2","porcentaje_beneficiario2",
    //             "parentesco_beneficiario2","external_id"
    //         ];

    //         foreach ($datos as $index => $fila) {

    //             $resumenCambios = [];
    //             $filaNumero = $index + 2;

    //             try {

    //                 $code = $fila['code'] ?? null;

    //                 if (!$code) {
    //                     throw new \Exception("No se encontró 'code'");
    //                 }

    //                 $employee = Employee::where('code', $code)->first();

    //                 if (!$employee) {
    //                     throw new \Exception("Empleado {$code} no existe");
    //                 }

    //                 $updatesPorTabla = [];
    //                 $benefitsToSync = null;
    //                 $newShiftRoleId = null;
    //                 $startDate = null;

    //                 $additionalInfo = is_array($employee->additional_info)
    //                     ? $employee->additional_info
    //                     : json_decode($employee->additional_info ?? '[]', true);

    //                 // =========================
    //                 // MAPEO DINÁMICO
    //                 // =========================
    //                 foreach ($fila as $campoBd => $valorOriginal) {

    //                     if ($campoBd === 'code') continue;

    //                     $valor = trim((string)$valorOriginal);
    //                     $valor = ($valor === '') ? null : $valor;

    //                     // ESPECIALES
    //                     if ($campoBd === 'benefits') {
    //                         $benefitsToSync = array_map('trim', explode(',', $valor));
    //                         continue;
    //                     }

    //                     if ($campoBd === 'shift_role_id') {
    //                         $newShiftRoleId = $valor;
    //                         continue;
    //                     }

    //                     if ($campoBd === 'start_date') {
    //                         $startDate = $valor;
    //                         continue;
    //                     }

    //                     if ($campoBd === 'employees_postal_code') {
    //                         $updatesPorTabla['addresses']['postal_code'] = $valor;
    //                         continue;
    //                     }

    //                     if ($campoBd === 'tax_postal_code') {
    //                         $updatesPorTabla['tax_data']['postal_code'] = $valor;
    //                         continue;
    //                     }

    //                     if (in_array($campoBd, $camposAdditionalInfo)) {
    //                         $additionalInfo[$campoBd] = $valor;
    //                         continue;
    //                     }

    //                     foreach ($estructura as $tabla => $campos) {
    //                         if (in_array($campoBd, $campos)) {
    //                             $updatesPorTabla[$tabla][$campoBd] = $valor;
    //                             break;
    //                         }
    //                     }
    //                 }

    //                 // =========================
    //                 // GUARDAR ADDITIONAL INFO
    //                 // =========================
    //                 $oldAdditional = is_array($employee->additional_info)
    //                     ? $employee->additional_info
    //                     : json_decode($employee->additional_info ?? '[]', true);

    //                 if ($oldAdditional != $additionalInfo) {

    //                     $employee->update([
    //                         'additional_info' => $additionalInfo
    //                     ]);

    //                     $resumenCambios['additional_info'] = [
    //                         'before' => $oldAdditional,
    //                         'after' => $additionalInfo
    //                     ];

    //                     Logs::create([
    //                         'action' => 'UPDATE',
    //                         'user_id' => auth()->id(),
    //                         'table_name' => 'employees',
    //                         'date' => now(),
    //                         'relationship_id' => $employee->id,
    //                         'old_data' => json_encode($resumenCambios['additional_info'])
    //                     ]);
    //                 }

    //                 // =========================
    //                 // UPDATES DINÁMICOS
    //                 // =========================
    //                 foreach ($updatesPorTabla as $tabla => $data) {

    //                     if (empty($data)) continue;

    //                     switch ($tabla) {

    //                         case 'employees':

    //                             $old = $employee->getOriginal();

    //                             $employee->update($data);

    //                             $changes = $employee->getChanges();

    //                             if (!empty($changes)) {

    //                                 $resumenCambios['employee'] = $changes;

    //                                 Logs::create([
    //                                     'action' => 'UPDATE',
    //                                     'user_id' => auth()->id(),
    //                                     'table_name' => 'employees',
    //                                     'date' => now(),
    //                                     'relationship_id' => $employee->id,
    //                                     'old_data' => json_encode([
    //                                         'before' => array_intersect_key($old, $changes),
    //                                         'after' => $changes,
    //                                         'fila' => $filaNumero,
    //                                         'code' => $code
    //                                     ])
    //                                 ]);
    //                             }

    //                         break;

    //                         case 'payment_data':

    //                             $old = PaymentData::where('owner_id', $employee->id)
    //                                 ->where('owner_type', Employee::class)
    //                                 ->first();

    //                             $oldData = $old ? $old->getAttributes() : [];

    //                             $model = PaymentData::updateOrCreate(
    //                                 ['owner_id' => $employee->id, 'owner_type' => Employee::class],
    //                                 $data
    //                             );

    //                             if ($oldData != $model->getAttributes()) {

    //                                 $resumenCambios['payment'] = [
    //                                     'before' => $oldData,
    //                                     'after' => $model->getAttributes()
    //                                 ];

    //                                 Logs::create([
    //                                     'action' => 'UPDATE',
    //                                     'user_id' => auth()->id(),
    //                                     'table_name' => 'payment_data',
    //                                     'date' => now(),
    //                                     'relationship_id' => $employee->id,
    //                                     'old_data' => json_encode($resumenCambios['payment'])
    //                                 ]);
    //                             }

    //                         break;

    //                         case 'addresses':

    //                             $old = Address::where('addressable_id', $employee->id)
    //                                 ->where('addressable_type', Employee::class)
    //                                 ->first();

    //                             $oldData = $old ? $old->getAttributes() : [];

    //                             $model = Address::updateOrCreate(
    //                                 ['addressable_id' => $employee->id, 'addressable_type' => Employee::class],
    //                                 $data
    //                             );

    //                             if ($oldData != $model->getAttributes()) {

    //                                 $resumenCambios['address'] = [
    //                                     'before' => $oldData,
    //                                     'after' => $model->getAttributes()
    //                                 ];

    //                                 Logs::create([
    //                                     'action' => 'UPDATE',
    //                                     'user_id' => auth()->id(),
    //                                     'table_name' => 'addresses',
    //                                     'date' => now(),
    //                                     'relationship_id' => $employee->id,
    //                                     'old_data' => json_encode($resumenCambios['address'])
    //                                 ]);
    //                             }

    //                         break;

    //                         case 'tax_data':

    //                             $old = TaxData::where('owner_id', $employee->id)
    //                                 ->where('owner_type', Employee::class)
    //                                 ->first();

    //                             $oldData = $old ? $old->getAttributes() : [];

    //                             $model = TaxData::updateOrCreate(
    //                                 ['owner_id' => $employee->id, 'owner_type' => Employee::class],
    //                                 $data
    //                             );

    //                             if ($oldData != $model->getAttributes()) {

    //                                 $resumenCambios['tax'] = [
    //                                     'before' => $oldData,
    //                                     'after' => $model->getAttributes()
    //                                 ];

    //                                 Logs::create([
    //                                     'action' => 'UPDATE',
    //                                     'user_id' => auth()->id(),
    //                                     'table_name' => 'tax_data',
    //                                     'date' => now(),
    //                                     'relationship_id' => $employee->id,
    //                                     'old_data' => json_encode($resumenCambios['tax'])
    //                                 ]);
    //                             }

    //                         break;
    //                     }
    //                 }

    //                 // =========================
    //                 // SHIFT
    //                 // =========================
    //                 if ($newShiftRoleId) {

    //                     $currentShift = EmployeeShiftRole::where('employee_id', $employee->id)
    //                         ->where('active', 1)
    //                         ->first();

    //                     if (!$currentShift || $currentShift->shift_role_id != $newShiftRoleId) {

    //                         $oldShift = $currentShift ? $currentShift->getAttributes() : null;

    //                         if ($currentShift) {
    //                             $currentShift->update([
    //                                 'active' => 0,
    //                                 'end_date' => now()->format('Y-m-d')
    //                             ]);
    //                         }

    //                         $newShift = EmployeeShiftRole::create([
    //                             'employee_id' => $employee->id,
    //                             'shift_role_id' => $newShiftRoleId,
    //                             'start_date' => $startDate ?? now()->format('Y-m-d'),
    //                             'active' => 1,
    //                             'branch_office_id' => $employee->branch_office_id
    //                         ]);

    //                         $resumenCambios['shift'] = [
    //                             'before' => $oldShift,
    //                             'after' => $newShift->getAttributes()
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_shift_roles',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode($resumenCambios['shift'])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // BENEFITS
    //                 // =========================
    //                 if ($benefitsToSync !== null) {

    //                     $oldBenefits = $employee->benefits()->pluck('id')->toArray();

    //                     $benefitIds = Benefit::whereIn('name', $benefitsToSync)
    //                         ->pluck('id')->toArray();

    //                     $employee->benefits()->sync($benefitIds);

    //                     if ($oldBenefits != $benefitIds) {

    //                         $resumenCambios['benefits'] = [
    //                             'before' => $oldBenefits,
    //                             'after' => $benefitIds
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_benefits',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode($resumenCambios['benefits'])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // RESPUESTA
    //                 // =========================
    //                 $nombreCompleto = trim(
    //                     ($employee->name ?? '') . ' ' .
    //                     ($employee->surname ?? '') . ' ' .
    //                     ($employee->mother_surname ?? '')
    //                 );

    //                 $insertados++;

    //                 $insertadosLista[] = [
    //                     'id' => $employee->id,
    //                     'nombre' => $nombreCompleto,
    //                     'cambios' => $resumenCambios
    //                 ];

    //             } catch (\Throwable $e) {

    //                 $fallidos++;

    //                 $fallidosLista[] = [
    //                     'id' => $fila['code'] ?? 'N/A',
    //                     'motivo' => $e->getMessage()
    //                 ];
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'actualizados' => $insertados,
    //             'fallidos' => $fallidos,
    //             'insertados_lista' => $insertadosLista,
    //             'fallidos_lista' => $fallidosLista
    //         ]);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function massiveUpdate(Request $request)
    // {
    //     $datos = $request->datos;

    //     $insertados = 0;
    //     $fallidos = 0;
    //     $insertadosLista = [];
    //     $fallidosLista = [];
    //     $resumenCambios = [];

    //     DB::beginTransaction();

    //     try {

    //         foreach ($datos as $index => $fila) {

    //             $filaNumero = $index + 2;

    //             try {

    //                 $code = $fila['code'] ?? null;

    //                 if (!$code) {
    //                     throw new \Exception("No se encontró 'code'");
    //                 }

    //                 $employee = Employee::where('code', $code)->first();

    //                 if (!$employee) {
    //                     throw new \Exception("Empleado {$code} no existe");
    //                 }

    //                 $oldData = $employee->getOriginal();

    //                 $updatesEmployees = [];
    //                 $updatesPayment = [];
    //                 $updatesAddress = [];
    //                 $updatesTax = [];

    //                 $updatesAdditionalInfo = is_array($employee->additional_info)
    //                     ? $employee->additional_info
    //                     : json_decode($employee->additional_info ?? '[]', true);

    //                 $benefitsToSync = null;
    //                 $newShiftRoleId = null;

    //                 foreach ($fila as $campoBd => $valorOriginal) {

    //                     if ($campoBd === 'code') continue;

    //                     $valor = trim((string)$valorOriginal);
    //                     $valor = ($valor === '') ? null : $valor;

    //                     // --- EMPLOYEE ---
    //                     if (in_array($campoBd, [
    //                         'name','surname','mother_surname','email','personal_phone',
    //                         'company_phone','status','dni','health_id',
    //                         'department_id','position_id','gender_id',
    //                         'birthday','entry_date','termination_date',
    //                         'branch_office_id','employee_parent_id'
    //                     ])) {
    //                         $updatesEmployees[$campoBd] = $valor;
    //                     }

    //                     // --- PAYMENT ---
    //                     elseif (in_array($campoBd, [
    //                         'salary','daily_salary','bank_id','payment_method_id',
    //                         'account_number','account_card','account_code'
    //                     ])) {
    //                         $updatesPayment[$campoBd] = $valor;
    //                     }

    //                     // --- ADDRESS ---
    //                     elseif (in_array($campoBd, [
    //                         'street','external_number','internal_number',
    //                         'location_id','city_id','state_id','postal_code'
    //                     ])) {
    //                         $updatesAddress[$campoBd] = $valor;
    //                     }

    //                     elseif ($campoBd === 'employees_postal_code') {
    //                         $updatesAddress['postal_code'] = $valor;
    //                     }

    //                     // --- TAX ---
    //                     elseif (in_array($campoBd, [
    //                         'tax_id','tax_system_id','tax_postal_code'
    //                     ])) {
    //                         $updatesTax[$campoBd === 'tax_postal_code' ? 'postal_code' : $campoBd] = $valor;
    //                     }

    //                     // --- ADDITIONAL INFO ---
    //                     elseif (in_array($campoBd, [
    //                         'profession','level_education','civil_state',
    //                         'beneficiary_name','beneficiary_kinship',
    //                         'nombre_beneficiario2','parentesco_beneficiario2',
    //                         'porcentaje_beneficiario','porcentaje_beneficiario2',
    //                         'days_duration','tipo_jornada'
    //                     ])) {
    //                         $updatesAdditionalInfo[$campoBd] = $valor;
    //                     }

    //                     // --- BENEFITS ---
    //                     elseif ($campoBd === 'benefits') {
    //                         $benefitsToSync = array_map('trim', explode(',', $valor));
    //                     }

    //                     // --- SHIFT ---
    //                     elseif ($campoBd === 'shift_role_id') {
    //                         $newShiftRoleId = $valor;
    //                     }
    //                     elseif ($campoBd === 'start_date') {
    //                         $startDate = $valor;
    //                     }
    //                 }

    //                 // =========================
    //                 // EMPLOYEE
    //                 // =========================
    //                 if (!empty($updatesEmployees)) {
    //                     $employee->update($updatesEmployees);
    //                 }

    //                 // =========================
    //                 // ADDITIONAL INFO
    //                 // =========================
    //                 if ($updatesAdditionalInfo != $employee->additional_info) {
    //                     $employee->update(['additional_info' => $updatesAdditionalInfo]);
    //                 }

    //                 $changes = $employee->getChanges();

    //                 if (!empty($changes)) {
    //                     $resumenCambios['employee'] = $changes;

    //                     Logs::create([
    //                         'action'          => 'UPDATE',
    //                         'user_id'         => auth()->id(),
    //                         'table_name'      => 'employees',
    //                         'date'            => Carbon::now('America/Mexico_City'),
    //                         'relationship_id' => $employee->id,

    //                         'old_data' => json_encode([
    //                             'before' => array_intersect_key($oldData, $changes),
    //                             'after'  => $changes,
    //                             'fila'   => $filaNumero,
    //                             'code'   => $code
    //                         ])
    //                     ]);
    //                 }

    //                 // =========================
    //                 // PAYMENT
    //                 // =========================

    //                 $payment = PaymentData::where('owner_id', $employee->id)
    //                     ->where('owner_type', Employee::class)
    //                     ->first();

    //                 $oldPayment = $payment ? $payment->getAttributes() : [];

    //                 $newPayment = null;

    //                 if (!empty($updatesPayment)) {
    //                     PaymentData::updateOrCreate(
    //                         ['owner_id' => $employee->id, 'owner_type' => Employee::class],
    //                         $updatesPayment
    //                     );

    //                     $newPayment = $payment->getAttributes();

    //                     if ($oldPayment != $newPayment) {
    //                         $resumenCambios['payment'] = [
    //                             'before' => $oldPayment,
    //                             'after' => $newPayment
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'payment_data',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode([
    //                                 'before' => $oldPayment,
    //                                 'after' => $newPayment
    //                             ])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // ADDRESS
    //                 // =========================

    //                 $address = Address::where('addressable_id', $employee->id)
    //                     ->where('addressable_type', Employee::class)
    //                     ->first();

    //                 $oldAddress = $address ? $address->getAttributes() : [];

    //                 if (!empty($updatesAddress)) {

    //                     Address::updateOrCreate(
    //                         ['addressable_id' => $employee->id, 'addressable_type' => Employee::class],
    //                         $updatesAddress
    //                     );

    //                     $newAddress = $address->getAttributes();

    //                     if ($oldAddress != $newAddress) {
    //                         if ($oldAddress != $newAddress) {
    //                             $resumenCambios['address'] = [
    //                                 'before' => $oldAddress,
    //                                 'after' => $newAddress
    //                             ];
    //                         }

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'addresses',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode([
    //                                 'before' => $oldAddress,
    //                                 'after' => $newAddress
    //                             ])
    //                         ]);
    //                     }
    //                 }



    //                 // =========================
    //                 // TAX
    //                 // =========================

    //                 $tax = TaxData::where('owner_id', $employee->id)
    //                     ->where('owner_type', Employee::class)
    //                     ->first();

    //                 $oldTax = $tax ? $tax->getAttributes() : [];

    //                 if (!empty($updatesTax)) {

    //                     TaxData::updateOrCreate(
    //                         ['owner_id' => $employee->id, 'owner_type' => Employee::class],
    //                         $updatesTax
    //                     );

    //                     $newTax = $tax->getAttributes();

    //                     if ($oldTax != $newTax) {
    //                         $resumenCambios['tax'] = [
    //                             'before' => $oldTax,
    //                             'after' => $newTax
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'tax_data',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode([
    //                                 'before' => $oldTax,
    //                                 'after' => $newTax
    //                             ])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // SHIFT
    //                 // =========================
    //                 if ($newShiftRoleId) {

    //                     $currentShift = EmployeeShiftRole::where('employee_id', $employee->id)
    //                         ->where('active', 1)
    //                         ->first();

    //                     if (!$currentShift || $currentShift->shift_role_id != $newShiftRoleId) {

    //                         $oldShift = $currentShift ? $currentShift->getAttributes() : null;

    //                         if ($currentShift) {
    //                             $currentShift->update([
    //                                 'active' => 0,
    //                                 'end_date' => now()->format('Y-m-d')
    //                             ]);
    //                         }

    //                         $newShift = EmployeeShiftRole::create([
    //                             'employee_id' => $employee->id,
    //                             'shift_role_id' => $newShiftRoleId,
    //                             'start_date' => $start_date,
    //                             'active' => 1,
    //                             'branch_office_id' => $employee->branch_office_id
    //                         ]);

    //                         $resumenCambios['shift'] = [
    //                             'before' => $oldShift,
    //                             'after' => $newShift->getAttributes()
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_shift_roles',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode([
    //                                 'before' => $oldShift,
    //                                 'after' => $newShift->getAttributes()
    //                             ])
    //                         ]);
    //                     }
    //                 }

    //                 // =========================
    //                 // BENEFITS
    //                 // =========================
    //                 if ($benefitsToSync !== null) {

    //                     $oldBenefits = $employee->benefits()->pluck('id')->toArray();

    //                     $benefitIds = Benefit::whereIn('name', $benefitsToSync)
    //                         ->pluck('id')->toArray();

    //                     $employee->benefits()->sync($benefitIds);

    //                     if ($oldBenefits != $benefitIds) {
    //                         $resumenCambios['benefits'] = [
    //                             'before' => $oldBenefits,
    //                             'after' => $benefitIds
    //                         ];

    //                         Logs::create([
    //                             'action' => 'UPDATE',
    //                             'user_id' => auth()->id(),
    //                             'table_name' => 'employee_benefits',
    //                             'date' => now(),
    //                             'relationship_id' => $employee->id,
    //                             'old_data' => json_encode([
    //                                 'before' => $oldBenefits,
    //                                 'after' => $benefitIds
    //                             ])
    //                         ]);
    //                     }
    //                 }

    //                 $nombreCompleto = trim(
    //                     ($employee->name ?? '') . ' ' .
    //                     ($employee->surname ?? '') . ' ' .
    //                     ($employee->mother_surname ?? '')
    //                 );

    //                 $insertados++;

    //                 $insertadosLista[] = [
    //                     'id' => $employee->id,
    //                     'nombre' => $nombreCompleto,
    //                     'cambios' => $resumenCambios
    //                 ];

    //             } catch (\Throwable $e) {

    //                 $fallidos++;

    //                 $fallidosLista[] = [
    //                     'id' => $fila['code'] ?? 'N/A',
    //                     'motivo' => $e->getMessage()
    //                 ];
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'actualizados' => $insertados,
    //             'fallidos' => $fallidos,
    //             'insertados_lista' => $insertadosLista,
    //             'fallidos_lista' => $fallidosLista
    //         ]);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

}
