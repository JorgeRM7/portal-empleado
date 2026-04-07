<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollDepartmentItem extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_department_items';

    protected $fillable = [
        'clave',
        'nombre_del_trabajador',
        'nss',
        'rfc',
        'curp',
        'fecha_de_alta',
        'departamento',
        'puesto',
        'tipo_salario',
        'salario_diario',
        'sdi',
        'dias_trabajados',
        'faltas',
        'sueldo',
        'comisiones',
        'horas_extras_dobles',
        'aguinaldo',
        'horas_extras_triples',
        'fondo_de_ahorro_patron',
        'prestamo_del_fondo',
        'intereses_del_fondo',
        'vacaciones',
        'prima_vacacional',
        'reparto_de_utilidades',
        'alimentacion',
        'habitacion',
        'vales_de_despensa',
        'premios_de_asistencia',
        'premios_de_puntualidad',
        'prima_dominical',
        'subsidios_por_incapacidad',
        'vacaciones_disfrutadas',
        'indemnizacion',
        'prima_de_antiguedad',
        'premio_de_produccion',
        'gastos_funerarios',
        'reposicion_de_turno',
        'compensacion',
        'gratificacion_por_retiro',
        'prestamo_personal',
        'descanso_laborado',
        'devolucion_credito_fonacot',
        'devolucion_credito_infonavit',
        'ayuda_escolar',
        'dia_festivo',
        'becas_educacionales',
        'ayuda_para_capacitacion',
        'apoyo_de_transportev',
        'apoyo_familiar_act_recreat',
        'inc_familiar_fiestas_navide',
        'apoyo_de_transportef',
        'total_imss',
        'total_isr',
        'subsidio_empleo',
        'isr',
        'imss',
        'anticipo_de_nomina',
        'fondo_de_ahorro',
        'reposicion_de_articulos',
        'pension_alimenticia',
        'sar_voluntario',
        'infonavit_voluntario',
        'credito_fonacot',
        'credito_infonavit',
        'subsidio_para_el_empleo',
        'impuesto_local',
        'isr_de_ingr_por_retiro',
        'total_percepciones',
        'total_deducciones',
        'total_efectivo',
        'total_en_especie',
        'neto_pagado',
        'payroll_department_id',
        'bono_semestral',
        'honorarios_asimilados',
        'estimulo_para_ejerc_fisico',
        'prestamo_personal_d',
        'alimentacion_d',
        'isr_de_ingr_por_retiro_d',
        'habitacion_d',
        'clase',
        'ubicacion',
        'active',
        'fondo_de_ahorro_trab_d',
        'compensacion_isr_a_favor_2024',
        'retencion_por_juicio_civil',
        'retencion_de_isr_ejer_ant',
        'fondo_ahorro_patron_exento',
        'devolucion_fondo_ahorro',
        'compensacion_isr_a_favor',
        'fondo_ahorro_patron_exento_v2',
        'fecha_de_alta',
        'active',
        'salario_diario',
        'sdi',
        'sueldo',
        'comisiones',
        'total_percepciones',
        'total_deducciones',
        'neto_pagado',
    ];

    protected $casts = [
        
    ];

    public function payrollDepartment()
    {
        return $this->belongsTo(PayrollDepartment::class);
    }

}