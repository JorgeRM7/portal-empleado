<?php

namespace App\Http\Controllers;

use App\Imports\PayrollImport;
use App\Models\PayrollDepartment;
use App\Models\PayrollTypes;
use App\Models\PayrollDepartmentItem;
use App\Models\Logs;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use DB;
use Storage;


class PayrollDepartamentsController
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request ){
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $payrrollTypes = PayrollTypes::select('id','name')->get();
        
        return Inertia::render('PayrollDepartaments/Index', [
            "branchOffices" => $branchOffices,
            "payrrollTypes" => $payrrollTypes
        ]);
    }

    public function filter_data(Request $request)
    {
        $weeks = $request->weeks ?? [];
        $years = $request->years ?? [];

        $branchOffices = $request->branch_offices ?? [];

        $query = PayrollDepartment::with([
            'payrollType:id,name',
            'payrollBranchOffice:id,code'
        ]); 
        
        if (!empty($weeks)) {
            $query->whereIn('week', $weeks);
        }

        if (!empty($years)) {
            $query->whereIn('year', $years);
        }


        if (!empty($branchOffices) && is_array($branchOffices)) {
            $query->whereIn('branch_office_id', $branchOffices);
        }

        $data = $query->get();

        return response()->json([
            'rows' => $data,
        ]);
    }

    public function create()
    {
        $user = auth()
            ->user();
        $branchOffices = $user->branchOffices()
            ->select('id', 'code')
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $payrrollTypes = PayrollTypes::select('id','name')->get();

        return Inertia::render('PayrollDepartaments/Create', [
            "branchOffices" => $branchOffices,
            "payrrollTypes" => $payrrollTypes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);
        DB::beginTransaction();

        try{

            $file           = $request->file('file_excel');
            $rows           = Excel::toArray([], $file);
            $sheet          = $rows[0];
            $headers        = $sheet[0];
            $data           = [];
            $filePdf        = $request->file('file_pdf');
            $excelName      = $file->getClientOriginalName();
            $pdfName        = $filePdf?->getClientOriginalName();
            $payrollTypeId  = $request->payroll_type_id;
            $branchOfficeId = $request->branch_office_id;
            $date           = Carbon::parse($request->date);
            $year           = $date->year;
            $month          = $date->month;
            $week           = $date->isoWeek;

            $disk = Storage::disk('remote_sftp');
            $dir = 'payroll';
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

            $payroll = PayrollDepartment::create([
                'in_file'           => $excelName,
                'out_file'          => $excelName,
                'out_file_employee' => $excelName,
                'year'              => $year,
                'week'              => $week,
                'month'             => $month,
                'date'              => $date,
                'user_id'           => auth()->id(),
                'branch_office_id'  => $branchOfficeId,
                'payroll_type_id'   => $payrollTypeId,
                'file_total'        => $pdfName,
            ]);

            $payrollId = $payroll->id;
            $items = [];

            function num($value)
            {
                if ($value === null || $value === '') {
                    return 0;
                }
                $value = str_replace(',', '', $value);

                return floatval($value);
            }

            foreach (array_slice($sheet, 1) as $row) {

                if (!array_filter($row, fn($value) => trim((string)$value) !== '')) {
                    continue;
                }
                $row = array_pad($row, count($headers), null);
                $row = array_combine($headers, $row);

                $items[] = [
                    'clave'                         => $row['Clave'] ?? null,
                    'nombre_del_trabajador'         => $row['Nombre del trabajador'] ?? null,
                    'nss'                           => $row['NSS'] ?? null,
                    'rfc'                           => $row['RFC'] ?? null,
                    'curp'                          => $row['CURP'] ?? null,
                    'fecha_de_alta'                 => $row['Fecha de Alta'] ?? null,
                    'departamento'                  => $row['Departamento'] ?? null,
                    'puesto'                        => $row['Puesto'] ?? null,
                    'tipo_salario'                  => $row['Tipo Salario'] ?? null,
                    'salario_diario'                => $row['Salario Diario'] ?? null,
                    'sdi'                           => $row['SDI'] ?? null,
                    'dias_trabajados'               => $row['Días trabajados'] ?? null,
                    'faltas'                        => $row['Faltas'] ?? null,
                    'sueldo'                        => num($row['SUELDO'] ?? 0),
                    'comisiones'                    => num($row['COMISIONES'] ?? 0) ,
                    'horas_extras_dobles'           => num($row['HORAS EXTRAS DOBLES'] ?? 0),
                    'aguinaldo'                     => num($row['AGUINALDO']) ?? 0,
                    'horas_extras_triples'          => num($row['HORAS EXTRAS TRIPLES'] ?? 0),
                    'fondo_de_ahorro_patron'        => num($row['FONDO DE AHORRO PATRON*'] ?? 0),
                    'prestamo_del_fondo'            => num($row['PRESTAMO DEL FONDO'] ?? 0),
                    'intereses_del_fondo'           => num($row['INTERESES DEL FONDO'] ?? 0),
                    'vacaciones'                    => num($row['VACACIONES'] ?? 0),
                    'prima_vacacional'              => num($row['PRIMA VACACIONAL'] ?? 0),
                    'reparto_de_utilidades'         => num($row['REPARTO DE UTILIDADES'] ?? 0),
                    'alimentacion'                  => num($row['ALIMENTACION*'] ?? 0),
                    'habitacion'                    => num($row['HABITACION'] ?? 0),
                    'vales_de_despensa'             => num($row['VALES DE DESPENSA'] ?? 0),
                    'premios_de_asistencia'         => num($row['PREMIOS DE ASISTENCIA'] ?? 0),
                    'premios_de_puntualidad'        => num($row['PREMIOS DE PUNTUALIDAD'] ?? 0),
                    'prima_dominical'               => num($row['PRIMA DOMINICAL'] ?? 0),
                    'subsidios_por_incapacidad'     => num($row['SUBSIDIOS POR INCAPACIDAD'] ?? 0),
                    'vacaciones_disfrutadas'        => num($row['VACACIONES DISFRUTADAS'] ?? 0),
                    'indemnizacion'                 => num($row['INDEMNIZACION'] ?? 0),
                    'prima_de_antiguedad'           => num($row['PRIMA DE ANTIGUEDAD'] ?? 0),
                    'premio_de_produccion'          => num($row['PREMIO DE PRODUCCION'] ?? 0),
                    'gastos_funerarios'             => num($row['GASTOS FUNERARIOS'] ?? 0),
                    'reposicion_de_turno'           => num($row['REPOSICION DE TURNO'] ?? 0),
                    'compensacion'                  => num($row['COMPENSACION'] ?? 0),
                    'gratificacion_por_retiro'      => num($row['GRATIFICACION POR RETIRO'] ?? 0),
                    'prestamo_personal'             => num($row['PRESTAMO PERSONAL'] ?? 0),
                    'descanso_laborado'             => num($row['DESCANSO LABORADO'] ?? 0),
                    'devolucion_credito_fonacot'    => num($row['DEVOLUCION CREDITO FONACOT'] ?? 0),
                    'devolucion_credito_infonavit'  => num($row['DEVOLUCION CREDITO INFONAVIT'] ?? 0),
                    'ayuda_escolar'                 => num($row['AYUDA ESCOLAR'] ?? 0),
                    'dia_festivo'                   => num($row['DIA FESTIVO'] ?? 0),
                    'becas_educacionales'           => num($row['BECAS EDUCACIONALES'] ?? 0),
                    'ayuda_para_capacitacion'       => num($row['AYUDA PARA CAPACITACION'] ?? 0),
                    'apoyo_de_transportev'          => num($row['APOYO DE TRANSPORTE V'] ?? 0),
                    'apoyo_familiar_act_recreat'    => num($row['APOYO FAMILIAR ACT RECREATIV'] ?? 0),
                    'inc_familiar_fiestas_navide'   => num($row['INC FAMILIAR POR FIESTA NAVI'] ?? 0),
                    'apoyo_de_transportef'          => num($row['APOYO DE TRANSPORTE F'] ?? 0),
                    'total_imss'                    => num($row['Total IMSS'] ?? 0),
                    'total_isr'                     => num($row['Total ISR'] ?? 0),
                    'subsidio_empleo'               => num($row['Subsidio Empleo'] ?? 0),
                    'isr'                           => num($row['ISR'] ?? 0),
                    'imss'                          => num($row['IMSS'] ?? 0),
                    'anticipo_de_nomina'            => num($row['ANTICIPO DE NOMINA'] ?? 0),
                    'fondo_de_ahorro'               => num($row['DEVOLUCION FONDO DE AHORRO'] ?? 0),
                    'reposicion_de_articulos'       => num($row['REPOSICION DE ARTICULOS'] ?? 0),
                    'pension_alimenticia'           => num($row['PENSION ALIMENTICIA'] ?? 0),
                    'sar_voluntario'                => num($row['SAR VOLUNTARIO'] ?? 0),
                    'infonavit_voluntario'          => num($row['INFONAVIT VOLUNTARIO']?? 0),
                    'credito_fonacot'               => num($row['CREDITO FONACOT'] ?? 0),
                    'credito_infonavit'             => num($row['CREDITO INFONAVIT'] ?? 0),
                    'subsidio_para_el_empleo'       => num($row['SUBSIDIO PARA EL EMPLEO'] ?? 0),
                    'impuesto_local'                => num($row['IMPUESTO LOCAL'] ?? 0),
                    'isr_de_ingr_por_retiro'        => num($row['ISR DE INGR. POR RETIRO'] ?? 0),
                    'total_percepciones'            => num($row['Total Percepciones'] ?? 0),
                    'total_deducciones'             => num($row['Total Deducciones']?? 0),
                    'total_efectivo'                => num($row['Total Efectivo'] ?? 0),
                    'total_en_especie'              => num($row['Total en Especie'] ?? 0),
                    'neto_pagado'                   => num($row['Neto Pagado'] ?? 0),
                    'payroll_department_id'         => num($payrollId ?? 0),
                    'bono_semestral'                => num($row['BONO SEMESTRAL'] ?? 0),
                    'honorarios_asimilados'         => num($row['HONORARIOS ASIMILADOS'] ?? 0),
                    'estimulo_para_ejerc_fisico'    => num($row['ESTIMULO PARA EJERC FISICO'] ?? 0),
                    'prestamo_personal_d'           => num($row['PRESTAMO PERSONAL D'] ?? 0),
                    'alimentacion_d'                => num($row['ALIMENTACION D'] ?? 0),
                    'isr_de_ingr_por_retiro_d'      => num($row['ISR DE INGR. POR RETIRO'] ?? 0),
                    'habitacion_d'                  => num($row['HABITACION'] ?? 0),
                    'clase'                         => null,
                    'ubicacion'                     => num($row['UBICACIÓN'] ?? 0),
                    'active'                        => 1,
                    'fondo_de_ahorro_trab_d'        => num($row['FONDO DE AHORRO TRAB D'] ?? 0),
                    'compensacion_isr_a_favor_2024' => num($row['COMPENSACION ISR A FAVOR 2024']  ?? 0),
                    'retencion_por_juicio_civil'    => num($row['RETENCION POR JUICIO CIVIL']  ?? 0),
                    'retencion_de_isr_ejer_ant'     => num($row['RETENCION DE ISR EJER ANT']  ?? 0),
                    'fondo_ahorro_patron_exento'    => num($row['FONDO DE AHORRO PATRON*.EXENTO']  ?? 0),
                    'devolucion_fondo_ahorro'       => num($row['DEVOLUCION FONDO DE AHORRO']  ?? 0),
                    'compensacion_isr_a_favor'      => num($row['COMPENSACION DE ISR']  ?? 0),
                    'fondo_ahorro_patron_exento_v2' => num($row['FONDO DE AHORRO PATRON*.EXENTO'] ?? 0),
                ];
            }


            PayrollDepartmentItem::insert($items);

            $totals = PayrollDepartmentItem::where('payroll_department_id', $payrollId)
            ->selectRaw("
                COUNT(id) as employees,
                SUM(CAST(REPLACE(total_percepciones, ',', '') AS DECIMAL(10,2))) as total_debit,
                SUM(CAST(REPLACE(total_deducciones, ',', '') AS DECIMAL(10,2))) as total_credit,
                SUM(CAST(REPLACE(neto_pagado, ',', '') AS DECIMAL(10,2))) as total
            ")
            ->first();

            PayrollDepartment::where('id', $payrollId)->update([
                'credit'    => $totals->total_credit ?? 0,
                'debit'     => $totals->total_debit ?? 0,
                'total'     => $totals->total ?? 0,
                'employees' => $totals->employees ?? 0
            ]);

            $log = Logs::create([
                'action'          => 'INSERT',
                'user_id'         => auth()->id(),
                'table_name'      => 'payroll_departments',
                'date'            => Carbon::now('America/Mexico_City'),
                'old_data'        => null,
                'relationship_id' => $payrollId
            ]);


            DB::commit();
            return redirect()
                ->route('payroll-departaments')
                ->with('success', 'Nomina procesada correctamente');
        }catch (\Throwable $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la nomina',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadDocumentHostinger($recordID)
    {
        $payroll = PayrollDepartment::findOrFail($recordID);

        if (!$payroll->out_file_employee || !$payroll->file_total) {
            abort(404, 'Este registro no tiene documentos adjuntos.');
        }

        $disk = Storage::disk('remote_sftp');

        $path_excel = 'payroll/' . $payroll->out_file_employee;
        $path_pdf   = 'payroll/' . $payroll->file_total;

        $excel = null;
        $pdf = null;

        if ($disk->exists($path_excel)) {
            $excel = $disk->get($path_excel);
        } else {
            $url_excel = 'http://nominas.grupo-ortiz.site/Nominas/' . ltrim($payroll->out_file_employee, '/');
            $responseExcel = Http::timeout(60)->get($url_excel);

            if ($responseExcel->successful()) {
                $excel = $responseExcel->body();
            }
        }

        if ($disk->exists($path_pdf)) {
            $pdf = $disk->get($path_pdf);
        } else {
            $url_pdf = 'http://nominas.grupo-ortiz.site/Nominas/' . ltrim($payroll->file_total, '/');
            $responsePdf = Http::timeout(60)->get($url_pdf);

            if ($responsePdf->successful()) {
                $pdf = $responsePdf->body();
            }
        }

        if (!$excel || !$pdf) {
            abort(404, 'Uno o ambos archivos no se encontraron ni en Hostinger ni en la ruta alterna.');
        }

        $zipName = "documentos_nomina_{$recordID}.zip";
        $zipPath = storage_path("app/$zipName");

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFromString(basename($payroll->out_file_employee), $excel);
            $zip->addFromString(basename($payroll->file_total), $pdf);
            $zip->close();
        } else {
            abort(500, 'No se pudo generar el archivo ZIP.');
        }
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function destroy($recordID)
    {
        $record = PayrollDepartment::find($recordID);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        DB::beginTransaction();

        try {

            PayrollDepartmentItem::where('payroll_department_id', $recordID)->delete();
            $record->delete();

            $log = Logs::create([
                'action'          => 'DELETE',
                'user_id'         => auth()->id(),
                'table_name'      => 'payroll_departments',
                'date'            => Carbon::now('America/Mexico_City'),
                'old_data'        => null,
                'relationship_id' => $recordID
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
