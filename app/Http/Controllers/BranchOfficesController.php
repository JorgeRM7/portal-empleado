<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BranchOffice;
use App\Models\Company;
use App\Models\InvoiceLocation;
use App\Models\EmployeeClass;
use App\Models\Departments;
use App\Models\BranchOfficeFiscalKey;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BranchOfficesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $branchOffices = BranchOffice::all();
        $branchOffices = BranchOffice::indexList();

        return Inertia::render('BranchOffices/Index', [
            'BranchOffices' => $branchOffices
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        return Inertia::render('BranchOffices/Create', [
            'Companies' => Company::select('id', 'name')->get(),
            'InvoiceLocations' => InvoiceLocation::select('code', 'name')->get(),
            'EmployeeClasses' => EmployeeClass::select('code', 'name')->get(),
            'Departments' => Departments::select('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            // --------- GENERALES ----------
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branch_offices', 'name'),
            ],
            'clave' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branch_offices', 'code'),
            ],
            'empresa' => 'required|integer|exists:companies,id',
            'isActive'=> 'required|boolean',

            // --------- FLAGS ----------
            'tieneReporteVentas' => 'required|boolean',
            'tieneClasificacion' => 'required|boolean',

            // --------- ARRAYS ----------
            'employeeClasses' => 'nullable|array',

            // --------- DATOS FISCALES ----------
            'direccion'          => 'required|string',
            'representanteLegal' => 'required|string',
            'rfc'                => ['required','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'registroPatronal'   => 'required|string',
            'telefono'           => ['required','regex:/^\d{10}$/'],
            'actividad'          => 'required|string',
            'codigoPostal'       => 'required|digits:5',
            'numTurnos'          => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {

            $data = $request->all();

            /* ===============================
            1️⃣ Branch Office (campos directos)
            =============================== */

            $branch = BranchOffice::create([
                'name'                 => $data['nombre'],
                'code'                 => $data['clave'],
                'active'               => $data['isActive'] ? 1 : 0,
                'company_id'           => $data['empresa'],
                'internal_code'        => $data['claveSubsidiaria'],
                'external_location_id' => $data['ubicacion'],
                'payroll_location_id'  => $data['ubicacionNomina'],
                'is_sales'             => $data['tieneReporteVentas'] ? 1 : 0,
                'has_employee_classes' => $data['tieneClasificacion'] ? 1 : 0,
            ]);

            /* ===============================
            2️⃣ Employee Classes (JSON)
            =============================== */

            if ($branch->has_employee_classes) {
                $branch->employee_classes = collect($data['employeeClasses'])
                    ->map(fn ($c) => [
                        'id'         => $c['clase'],
                        'department' => $c['department'],
                        'default'    => (bool) $c['isDefault'],
                    ])->toJson();
            }

            /* ===============================
            3️⃣ Meta (JSON en branch_offices)
            =============================== */

            $branch->meta = [
                'compensation_extra' => (bool) $data['compensacionExtra'],
                'reports_sales_id'   => $data['idReporteVentas'],
                'address'            => $data['direccion'],
                'legal_person'       => $data['representanteLegal'],
                'tax_id'             => $data['rfc'],
                'code'               => $data['registroPatronal'],
                'phone'              => $data['telefono'],
                'activity'           => $data['actividad'],
                'postal_code'        => $data['codigoPostal'],
                'schedules'          => $data['numTurnos'],

                // Recibos
                'payroll_after_week' => (bool) $data['recibos']['despuesSemana'],
                'payroll_rfc'        => (bool) $data['recibos']['usandoRfc'],
                'invoice_visible'    => (bool) $data['recibos']['visibleModulo'],
            ];

            $branch->save();

            /* ===============================
            4️⃣ FIEL (tabla aparte)
            =============================== */

            try {

                $folder = 'branch_offices/fiscal_keys';

                if ($request->hasFile('fiel.certificado')) {
                    $data['fiel']['certificado'] = $this->uploadFiscalFile(
                        $request->file('fiel.certificado'),
                        $folder,
                        'fiel.certificado',
                        ['cer'],
                        $branch->id,
                        'CER'
                    );
                }

                if ($request->hasFile('fiel.llave')) {
                    $data['fiel']['llave'] = $this->uploadFiscalFile(
                        $request->file('fiel.llave'),
                        $folder,
                        'fiel.llave',
                        ['key'],
                        $branch->id,
                        'KEY'
                    );
                }

            } catch (\Throwable $e) {

                // Log::error('SFTP upload failure (FIEL)', [
                //     'branch_office_id' => $branch_office->id ?? null,
                //     'message' => $e->getMessage(),
                //     'host' => config('filesystems.disks.remote_sftp.host'),
                //     'root' => config('filesystems.disks.remote_sftp.root'),
                // ]);

                throw $e;
            }

            // if ($data['fiel']['certificado'] && $data['fiel']['llave']) {
            if (
                !empty($data['fiel']['certificado']) ||
                !empty($data['fiel']['llave']) ||
                !empty($data['fiel']['password']) ||
                !empty($data['fiel']['fechaExpiracion'])
            ) {
                BranchOfficeFiscalKey::create([
                    'branch_office_id' => $branch->id,
                    'certificate_path' => $data['fiel']['certificado'],
                    'key_path'         => $data['fiel']['llave'],
                    'passphrase'       => $data['fiel']['password'],
                    'expires_at'       => $data['fiel']['fechaExpiracion'],
                ]);
            }
        });

        return redirect()
            ->route('branch-offices.index')
            ->with('success', 'Sucursal creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Inertia::render('BranchOffices/Show', [
            'BranchOffices' => BranchOffice::show($id),
            'Companies' => Company::select('id', 'name')->get(),
            'InvoiceLocations' => InvoiceLocation::select('code', 'name')->get(),
            'EmployeeClasses' => EmployeeClass::select('code', 'name')->get(),
            'Departments' => Departments::select('name')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // dd($fileToken->url('Logo.png'));

        return Inertia::render('BranchOffices/Edit', [
            'BranchOffices' => BranchOffice::show($id),
            'Companies' => Company::select('id', 'name')->get(),
            'InvoiceLocations' => InvoiceLocation::select('code', 'name')->get(),
            'EmployeeClasses' => EmployeeClass::select('code', 'name')->get(),
            'Departments' => Departments::select('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BranchOffice $branch_office)
    {
        // dd($request->all());
        
        // dd([
        //     'has_cert' => $request->hasFile('fiel.certificado'),
        //     'has_key'  => $request->hasFile('fiel.llave'),
        // ]);

        // if ($request->hasFile('fiel.certificado')) {
        //     $path = $request->file('fiel.certificado')
        //         ->store('debug', 'local');

        //     dd($path);
        // }

        // if ($request->hasFile('fiel.certificado')) {
        //     $file = $request->file('fiel.certificado');

        //     dd([
        //         'original_name' => $file->getClientOriginalName(),
        //         'original_ext'  => $file->getClientOriginalExtension(),
        //         'mime'          => $file->getMimeType(),
        //         'valid'         => $file->isValid(),
        //     ]);
        // }

        $request->validate([
            // --------- GENERALES ----------
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branch_offices', 'name')->ignore($branch_office->id),
            ],
            'clave' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branch_offices', 'code')->ignore($branch_office->id),
            ],
            'empresa' => 'required|integer|exists:companies,id',
            'isActive'=> 'required|boolean',

            // --------- FLAGS ----------
            'tieneReporteVentas' => 'required|boolean',
            'tieneClasificacion' => 'required|boolean',

            // --------- ARRAYS ----------
            'employeeClasses' => 'nullable|array',

            // --------- DATOS FISCALES ----------
            'direccion'          => 'required|string',
            'representanteLegal' => 'required|string',
            'rfc'                => ['required','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'registroPatronal'   => 'required|string',
            'telefono'           => ['required','regex:/^\d{10}$/'],
            'actividad'          => 'required|string',
            'codigoPostal'       => 'required|digits:5',
            'numTurnos'          => 'required|integer|min:1',

            'fiel.certificado' => 'nullable|file|max:5120',
            'fiel.llave'       => 'nullable|file|max:5120',
            
        ],
        [
            'clave.unique'  => 'La clave ya está registrada.',
            'nombre.unique' => 'El nombre de la sucursal ya existe.',
        ]);

        DB::transaction(function () use ($request, $branch_office) {

            $data = $request->all();

            /* ===============================
            1️⃣ Branch Office
            =============================== */

            $branch_office->update([
                'name'                 => $data['nombre'],
                'code'                 => $data['clave'],
                'active'               => $data['isActive'] ? 1 : 0,
                'company_id'           => $data['empresa'],
                'internal_code'        => $data['claveSubsidiaria'],
                'external_location_id' => $data['ubicacion'],
                'payroll_location_id'  => $data['ubicacionNomina'],
                'is_sales'             => $data['tieneReporteVentas'] ? 1 : 0,
                'has_employee_classes' => $data['tieneClasificacion'] ? 1 : 0,
            ]);

            /* ===============================
            2️⃣ Employee Classes (JSON)
            =============================== */

            if ($branch_office->has_employee_classes && !empty($data['employeeClasses'])) {
                $branch_office->employee_classes = collect($data['employeeClasses'])
                    ->map(fn ($c) => [
                        'id'         => $c['clase'],
                        'department' => $c['department'],
                        'default'    => (bool) $c['isDefault'],
                    ])->toJson();
            } else {
                $branch_office->employee_classes = null;
            }

            /* ===============================
            3️⃣ Meta (JSON)
            =============================== */

            $branch_office->meta = json_encode([
                'compensation_extra' => (bool) $data['compensacionExtra'],
                'reports_sales_id'   => $data['idReporteVentas'],
                'address'            => $data['direccion'],
                'legal_person'       => $data['representanteLegal'],
                'tax_id'             => $data['rfc'],
                'code'               => $data['registroPatronal'],
                'phone'              => $data['telefono'],
                'activity'           => $data['actividad'],
                'postal_code'        => $data['codigoPostal'],
                'schedules'          => $data['numTurnos'],

                // Recibos
                'payroll_after_week' => (bool) $data['recibos']['despuesSemana'],
                'payroll_rfc'        => (bool) $data['recibos']['usandoRfc'],
                'invoice_visible'    => (bool) $data['recibos']['visibleModulo'],
            ]);

            $branch_office->save();

            /* ===============================
            4️⃣ FIEL (update / create)
            =============================== */
            try {

                $folder = 'branch_offices/fiscal_keys';

                if ($request->hasFile('fiel.certificado')) {
                    $data['fiel']['certificado'] = $this->uploadFiscalFile(
                        $request->file('fiel.certificado'),
                        $folder,
                        'fiel.certificado',
                        ['cer'],
                        $branch_office->id,
                        'CER'
                    );
                }

                if ($request->hasFile('fiel.llave')) {
                    $data['fiel']['llave'] = $this->uploadFiscalFile(
                        $request->file('fiel.llave'),
                        $folder,
                        'fiel.llave',
                        ['key'],
                        $branch_office->id,
                        'KEY'
                    );
                }

            } catch (\Throwable $e) {

                // Log::error('SFTP upload failure (FIEL)', [
                //     'branch_office_id' => $branch_office->id ?? null,
                //     'message' => $e->getMessage(),
                //     'host' => config('filesystems.disks.remote_sftp.host'),
                //     'root' => config('filesystems.disks.remote_sftp.root'),
                // ]);

                throw $e;
            }

            if (
                !empty($data['fiel']['certificado']) ||
                !empty($data['fiel']['llave']) ||
                !empty($data['fiel']['password']) ||
                !empty($data['fiel']['fechaExpiracion'])
            ) {
                BranchOfficeFiscalKey::updateOrCreate(
                    ['branch_office_id' => $branch_office->id],
                    [
                        'certificate_path' => $data['fiel']['certificado'] ?? DB::raw('certificate_path'),
                        'key_path'         => $data['fiel']['llave'] ?? DB::raw('key_path'),
                        'passphrase'       => $data['fiel']['password'],
                        'expires_at'       => $data['fiel']['fechaExpiracion'],
                    ]
                );
            }
        });

        return redirect()
            ->route('branch-offices.index')
            ->with('success', 'Sucursal actualizada correctamente');
        // return response()->json(['success' => 'Sucursal actualizada correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BranchOffice $branch_office)
    {
        $branch_office->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:branch_offices,id',
        ]);

        BranchOffice::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Registros eliminados');
    }

    private function uploadFiscalFile(
        \Illuminate\Http\UploadedFile $file,
        string $folder,
        string $prefix,
        array $allowedExtensions,
        int $branchOfficeId,
        string $logLabel
    ): string {
        $disk = Storage::disk('remote_sftp');

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                $prefix => 'Archivo inválido. Extensiones permitidas: ' . implode(', ', $allowedExtensions),
            ]);
        }

        $disk->makeDirectory($folder);

        $today = Carbon::now('America/Mexico_City')->format('Ymd');
        $filename = "{$branchOfficeId}_{$extension}_{$today}.{$extension}";

        // Nombre controlado
        // $filename = $branchOfficeId . '_' . $originalName;
        // $remotePath = $folder . '/' . $filename;
        $remotePath = "{$folder}/{$filename}";

        if ($disk->exists($remotePath)) {
            $disk->delete($remotePath);
        }

        $ok = $disk->put($remotePath, fopen($file->getRealPath(), 'r'));

        // Log::info("SFTP upload {$logLabel}", [
        //     'branch_office_id' => $branchOfficeId,
        //     'path' => $remotePath,
        //     'success' => $ok,
        // ]);

        if (!$ok) {
            throw new \RuntimeException("No se pudo subir el archivo {$logLabel}");
        }

        return $filename;
    }

}
