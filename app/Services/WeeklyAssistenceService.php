<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;

class WeeklyAssistenceService
{
    /**
     * Revisar asistencias de un empleado en una fecha
     */
    public function horario(int $id, string $validity_from, int $schedule_incidence_id): array|false
    {
        $item = DB::table('employee_shift_roles')
            ->where('employee_id', $id)
            ->whereRaw('? BETWEEN start_date AND IFNULL(end_date, "2099-12-31")', [$validity_from])
            ->whereNull('deleted_at')
            ->orderByDesc('active')
            ->orderByDesc('start_date')
            ->first();

        if (!$item) {
            return [
                "estatus" => "error",
                "message" => "No se encontró un rol de turno activo"
            ];
        }

        $fecha_inicio   = $item->start_date;
        $shift_role_id  = $item->shift_role_id;
        $active         = $item->active ?? 0;

        $shift = DB::table('shift_roles')
            ->where('id', $shift_role_id)
            ->first();

        if (!$shift) {
            return [
                "estatus" => "error",
                "message" => "El rol de turno no existe en la base de datos"
            ];
        }

        $nombre_turno = $shift->name;
        $dinamico     = (bool) $shift->dynamic;
        $rules        = json_decode($shift->rules, true);

        $numero_dia = date("N", strtotime($validity_from));

        $mapa_dias = [
            1 => "monday_schedule_id",
            2 => "tuesday_schedule_id",
            3 => "wednesday_schedule_id",
            4 => "thursday_schedule_id",
            5 => "friday_schedule_id",
            6 => "saturday_schedule_id",
            7 => "sunday_schedule_id",
        ];

        $campo = $mapa_dias[$numero_dia];
        $turno_no_dinamico = $shift->{$campo} ?? null;

        if (!$dinamico) {
            $schedule_id = $turno_no_dinamico ?? 1;
        } else {

            $fecha_actual_bloque = new DateTime($fecha_inicio);
            $fecha_actual_bloque->setTime(0, 0);

            $hoy = new DateTime($validity_from);
            $hoy->setTime(0, 0);

            while ($fecha_actual_bloque <= $hoy) {

                foreach ($rules as $rule) {

                    $each = (int) ($rule['each'] ?? 0);
                    $type = $rule['type'] ?? 'days';

                    $inicio_bloque = clone $fecha_actual_bloque;
                    $fin_bloque = clone $fecha_actual_bloque;
                    $fin_bloque->modify("+$each $type -1 day")->setTime(0, 0);

                    if ($hoy >= $inicio_bloque && $hoy <= $fin_bloque) {

                        $schedule_id = $rule['schedule'] ?? 1;

                        if ($rule['rest'] == true) {
                            $schedule_id = 1;
                        }
                    }

                    $fecha_actual_bloque = clone $fin_bloque;
                    $fecha_actual_bloque->modify('+1 day')->setTime(0, 0);
                }
            }
        }

        if ($schedule_incidence_id != 0) {
            $schedule_id = $schedule_incidence_id;
        }

        $item = DB::table('schedules')
            ->selectRaw("
                schedules.id AS horario_id, 
                schedules.name AS horario, 
                schedules.entry_time AS hora_entrada,
                schedules.leave_time AS hora_salida,
                schedules.values,
                schedules.normal_double_overtime AS normal_tiempo_extra_doble,
                schedules.normal_triple_overtime AS normal_tiempo_extra_triple,
                schedules.double_overtime AS tiempo_extra_doble,
                schedules.triple_overtime AS tiempo_extra_triple,
                schedules.double_overtime_hour_value,
                schedules.triple_overtime_hour_value,
                schedules.active,
                schedules.tolerance_before_entry_time AS tolerancia_antes_entrada,
                schedules.tolerance_before_entry_type AS tipo_valor_toleracia_antes_entrada,
                schedules.tolerance_before_leave_time AS tolerancia_antes_salida,
                schedules.tolerance_before_leave_type AS tipo_valor_toleracia_antes_salida,
                schedules.tolerance_after_entry_time AS tolerancia_despues_entrada,
                schedules.tolerance_after_entry_type AS tipo_valor_toleracia_despues_entrada,
                schedules.tolerance_after_leave_time AS tolerancia_despues_salida,
                schedules.tolerance_after_leave_type AS tipo_valor_toleracia_despues_salida,
                schedules.tolerance_overtime_before_entry_time AS tolerancia_tiempo_extra_antes_entrada,
                schedules.tolerance_overtime_before_entry_type AS tipo_valor_tolerancia_tiempo_extra_antes_entrada,
                schedules.tolerance_overtime_before_leave_time AS tolerancia_tiempo_extra_antes_salida,
                schedules.tolerance_overtime_before_leave_type AS tipo_valor_tolerancia_tiempo_extra_antes_salida,
                schedules.tolerance_overtime_after_entry_time AS tolerancia_tiempo_extra_despues_entrada,
                schedules.tolerance_overtime_after_entry_type AS tipo_valor_tolerancia_tiempo_extra_despues_entrada,
                schedules.tolerance_overtime_after_leave_time AS tolerancia_tiempo_extra_despues_salida,
                schedules.tolerance_overtime_after_leave_type AS tipo_valor_tolerancia_tiempo_extra_despues_salida
            ")
            ->where(function ($query) use ($schedule_id) {
                $query->where(function ($q) use ($schedule_id) {
                    $q->where('schedules.id', $schedule_id)
                      ->where('active', 1);
                })->orWhere('schedules.id', 1);
            })
            ->orderByDesc('schedules.id')
            ->first();

        if (!$item) {
            return [
                "estatus" => "error",
                "message" => "El turno no existe en la base de datos"
            ];
        }

        // Datos base del horario
        $hora_entrada = $item->hora_entrada;
        $hora_salida  = $item->hora_salida;

        // Detectar turno nocturno
        $es_turno_nocturno = strtotime($hora_entrada) > strtotime($hora_salida);

        // ANTES DE ENTRADA
        $base_entrada = strtotime('2024-01-01 ' . $hora_entrada);

        $total_minutos = ($item->tipo_valor_toleracia_antes_entrada === 'hours')
            ? $item->tolerancia_antes_entrada * 60
            : $item->tolerancia_antes_entrada;

        $hora_tolerancia_antes_entrada_real =
            date('H:i:s', $base_entrada - ($total_minutos * 60));

        // DESPUÉS DE ENTRADA
        $total_minutos =
            ($item->tipo_valor_toleracia_despues_entrada === 'hours')
            ? $item->tolerancia_despues_entrada * 60
            : $item->tolerancia_despues_entrada;

        $hora_tolerancia_despues_entrada_real =
            date('H:i:s', $base_entrada + ($total_minutos * 60));

        // ANTES DE SALIDA
        $base_salida = strtotime('2024-01-01 ' . $hora_salida);

        $total_minutos =
            ($item->tipo_valor_toleracia_antes_salida === 'hours')
            ? $item->tolerancia_antes_salida * 60
            : $item->tolerancia_antes_salida;

        $hora_tolerancia_antes_salida_real =
            date('H:i:s', $base_salida - ($total_minutos * 60));

        // DESPUÉS DE SALIDA
        $total_minutos =
            ($item->tipo_valor_toleracia_despues_salida === 'hours')
            ? $item->tolerancia_despues_salida * 60
            : $item->tolerancia_despues_salida;

        $hora_tolerancia_despues_salida_real =
            date('H:i:s', $base_salida + ($total_minutos * 60));

        return [
            "estatus" => 'success',
            "tipo_turno" => $dinamico ? "dinamico" : "no_dinamico",
            "shift_role_id" => $shift_role_id,
            "nombre_turno" => $nombre_turno,
            "nombre_horario" => $item->horario,
            "fecha" => $validity_from,
            "schedule_id" => $item->horario_id,

            // Horario base
            "hora_entrada" => $hora_entrada,
            "hora_salida"  => $hora_salida,
            "es_turno_nocturno" => $es_turno_nocturno,

            // Tolerancias de entrada
            "tolerancia_antes_entrada" => $hora_tolerancia_antes_entrada_real,
            "tolerancia_despues_entrada" => $hora_tolerancia_despues_entrada_real,

            // Tolerancias de salida
            "tolerancia_antes_salida" => $hora_tolerancia_antes_salida_real,
            "tolerancia_despues_salida" => $hora_tolerancia_despues_salida_real,

            // Extras
            "tiempo_extra_doble" => $item->normal_tiempo_extra_doble,
            "tiempo_extra_triple" => $item->normal_tiempo_extra_triple,
            "valor_hora_doble" => $item->double_overtime_hour_value,
            "valor_hora_triple" => $item->triple_overtime_hour_value,
            "sql" => null,
        ];
    }

    public function hikcentral(int $id, string $validity_from, array $horario ): array
    {
        $fecha_inicio = date('Y-m-d', strtotime($validity_from));

        if ( ($horario['es_turno_nocturno'] ?? false) == true) {

            $fecha_inicio = $fecha_inicio . ' 06:00:00';
            $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +1 day'));
            $fecha_fin = $fecha_fin . ' 13:00:00';
        } else {
            $fecha_fin = $fecha_inicio . ' 23:59:59';
            $fecha_inicio = $fecha_inicio;
        }

        $checadas = DB::table('hikcentral')
            ->selectRaw("
                DATE(access_date_and_time) AS access_date,
                TIME(access_date_and_time) AS access_time,
                device_name
            ")
            ->where('employee_id', $id)
            ->where('access_date_and_time', '>=', $fecha_inicio)
            ->where('access_date_and_time', '<=', $fecha_fin)
            ->orderBy('access_date_and_time', 'asc')
            ->get()
            ->map(function ($row) {
                return (array) $row;
            })
            ->toArray();

        return $checadas;
    }

    public function checadas(int $id, string $validity_from, array $horario, array $incidencia)
    {
        $fecha_inicio = date('Y-m-d', strtotime($validity_from));

        if (($horario['es_turno_nocturno'] ?? false) == true) {
            $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +1 day'));
        } else {
            $fecha_fin = $fecha_inicio;
        }

        $inicio_turno = $fecha_inicio . ' ' . $horario['tolerancia_antes_entrada'];
        $fin_turno    = $fecha_fin . ' ' . $horario['tolerancia_despues_salida'];


        // if ( !empty($incidencia) && $incidencia['tipo_incidencia'] != 'INCIDENCIA_NORMAL' && $incidencia['incidence_id'] ?? null != 17 && !empty($incidencia['before_date'])) {
            
        //     $inicio_turno = $incidencia['before_date'];

        //     if (($horario['es_turno_nocturno'] ?? false) == true) {
        //         $fin_turno = date('Y-m-d', strtotime($fecha_inicio . ' +1 day')) . ' ' . $horario['tolerancia_despues_salida'];
        //         $inicio_turno = $fecha_inicio . ' ' . $horario['tolerancia_antes_entrada'];
        //     } else {
        //         $fin_turno = $incidencia['before_date'] . ' ' . $horario['tolerancia_despues_salida'];
        //     }
        // }

       
        $row = DB::table('hikcentral')
            ->selectRaw("
                TIME(MIN(access_date_and_time)) AS primera_checada,
                TIME(MAX(access_date_and_time)) AS ultima_checada,
                MIN(access_date_and_time) AS fecha_completa_entrada,
                MAX(access_date_and_time) AS fecha_completa_salida
            ")
            ->where('employee_id', $id)
            ->whereBetween('access_date_and_time', [$inicio_turno, $fin_turno])
            ->first();

        return  (array) $row;
    }

    public function incidencias(int $employee_id, string $fecha): array{
        $row = DB::table('employee_incidences')
            ->join('incidences', 'incidences.id', '=', 'employee_incidences.incidence_id')
            ->selectRaw("
                employee_incidences.*,
                CASE 
                    WHEN employee_incidences.before_date = ? THEN 'TRABAJA'
                    WHEN employee_incidences.rest_date = ? THEN 'DESCANSO'
                    WHEN ? BETWEEN employee_incidences.validity_from AND employee_incidences.validity_to
                        AND employee_incidences.before_date IS NULL
                        AND employee_incidences.rest_date IS NULL
                    THEN 'INCIDENCIA_NORMAL'
                    ELSE 'SIN_INCIDENCIA'
                END AS tipo_incidencia,
                incidences.requires_auth
            ", [$fecha, $fecha, $fecha])
            ->where('employee_incidences.employee_id', $employee_id)
            ->whereNull('employee_incidences.deleted_at')
            ->whereNull('employee_incidences.expires_at')
            ->where(function ($query) use ($fecha) {
                $query->whereDate('employee_incidences.before_date', $fecha)
                    ->orWhereDate('employee_incidences.rest_date', $fecha)
                    ->orWhere(function ($q) use ($fecha) {
                        $q->whereRaw('? BETWEEN employee_incidences.validity_from AND employee_incidences.validity_to', [$fecha])
                        ->whereNull('employee_incidences.before_date')
                        ->whereNull('employee_incidences.rest_date');
                    });
            })
            ->orderByDesc('employee_incidences.created_at')
            ->first();

        return $row ? (array) $row : [];
    }

    public function cumplio_turno(int $employee_id, array $incidencia, array $horario, array $horario_incidencia): array
    {
        $limite_horas_dia = 0;
        $cumplio = false;

        $fecha_inicio = $incidencia['before_date'];
        $resta = 0;

        if ( $horario['es_turno_nocturno'] == true  || $horario_incidencia['es_turno_nocturno'] == true) {
            $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +1 day'));
            $fecha_fin = $fecha_fin . ' ' . '08:00:00';
            $resta = 9;
        } else {
            $fecha_fin = $fecha_inicio . ' 23:59:59';
            $fecha_inicio = $fecha_inicio;
            $resta = 2;
        }

        $entrada_horario = strtotime($horario['hora_entrada']);
        $salida_horario  = strtotime($horario['hora_salida']);

        $entrada_incidencia = strtotime($horario_incidencia['hora_entrada']);
        $salida_incidencia  = strtotime($horario_incidencia['hora_salida']);

        $horas_horario = ($salida_horario - $entrada_horario) / 3600;
        $horas_incidencia = abs((($salida_incidencia - $entrada_incidencia) / 3600)) ?? 0;
        $horas_incidencia = $horas_incidencia -$resta;

        $row = DB::table('hikcentral')
            ->selectRaw("
                MIN(access_time) AS primera_checada,
                MAX(access_time) AS ultima_checada
            ")
            ->where('employee_id', $employee_id)
            ->whereBetween('access_date_and_time', [$fecha_inicio, $fecha_fin])
            ->first();

        $row = $row ? (array) $row : null;

        $primera = $row['primera_checada'] ?? null;
        $ultima  = $row['ultima_checada'] ?? null;

        $horas_trabajadas = 0;

        if ($primera && $ultima) {
            $horas_trabajadas = (strtotime($ultima) - strtotime($primera)) / 3600;
        }

        if ($horas_incidencia == $horas_horario) {
            $limite_horas_dia = abs($horas_incidencia);
        } else {
            $limite_horas_dia = abs($horas_incidencia + $horas_horario);
        }

        $limite_horas_dia = abs($limite_horas_dia) - 1;

        $horas_trabajadas = (float) $horas_trabajadas;
        $limite_horas_dia = (float) $limite_horas_dia;

        if ($horas_trabajadas > $limite_horas_dia) {
            $cumplio = true;
        }

        return [
            "cumplio" => $cumplio,
            "limite_horas_dia" => $limite_horas_dia,
            "inicio" => $fecha_inicio,
            "fin" => $fecha_fin,
            "primera_checada" => $primera,
            "ultima_checada" => $ultima,
            "horas_trabajadas" => $horas_trabajadas,
            "horas_incidencia" => $horas_incidencia,
            // "horas_horario" => $horas_horario,
        ];
    }

    public function validarAsistencia(array &$data): void{
        date_default_timezone_set('America/Mexico_City');

        $checadas = $data['checadas'] ?? [];
        $incidencia = $data['incidencia'] ?? [];
        $horario = $data['horario'] ?? [];
        $horario_incidencia = $data['horario_incidencia'] ?? [];
        $fecha = $data['fecha'];

        $hora_actual = date('H:i:s');
        $fecha_actual = date('Y-m-d');

        $entrada = $checadas['primera_checada'] ?? null;
        $salida = $checadas['ultima_checada'] ?? null;

        $codigo_asistencia = 2;
        $turno_cumplido = null;

        $scheduleId = $horario['schedule_id'] ?? null;
        $horaEntradaHorario = $horario['hora_entrada'] ?? null;
        $horaSalidaHorario = $horario['hora_salida'] ?? null;

        $tolAntesEntrada = $horario['tolerancia_antes_entrada'] ?? null;
        $tolDespuesEntrada = $horario['tolerancia_despues_entrada'] ?? null;
        $tolAntesSalida = $horario['tolerancia_antes_salida'] ?? null;

        // DESCANSO
        if ($scheduleId === 'Descanso' || $horaEntradaHorario === 'Descanso' || (int) $scheduleId === 1) {
            $codigo_asistencia = 2;
        }
        // SIN CHECADAS
        elseif (!$entrada) {
            $codigo_asistencia = 9;
        }
        // SOLO UNA CHECADA
        elseif ($entrada === $salida) {
            if ($entrada >= $tolAntesEntrada && $entrada <= $tolDespuesEntrada) {
                $codigo_asistencia = 31; // solo entrada / falta salida

                if ($fecha == $fecha_actual && $hora_actual < $horaSalidaHorario) {
                    $codigo_asistencia = 33; // laborando
                }
            } else {
                $codigo_asistencia = 30; // solo salida / falta entrada

                if ($fecha == $fecha_actual && $hora_actual < $horaSalidaHorario) {
                    $codigo_asistencia = 33; // laborando
                }
            }
        }
        // ENTRADA Y SALIDA
        else {
            $entro_a_tiempo =
                ($entrada >= $tolAntesEntrada && $entrada <= $tolDespuesEntrada);

            $salio_a_tiempo =
                ($salida >= $tolAntesSalida);

            $retardo =
                $entrada > $tolDespuesEntrada &&
                $salida > $horaSalidaHorario;

            if ($retardo && empty($incidencia)) {
                $codigo_asistencia = 52; // retardo
            } elseif (!$salio_a_tiempo) {
                $codigo_asistencia = 31; // sin salida

                if ($fecha == $fecha_actual && $hora_actual < $horaSalidaHorario) {
                    $codigo_asistencia = 33; // laborando
                }
            } else {
                $codigo_asistencia = 1; // asistencia completa
            }
        }

        // INCIDENCIA NORMAL
        if (
            ($data['incidencia']['tipo_incidencia'] ?? null) == 'INCIDENCIA_NORMAL' &&
            ($data['incidencia']['incidence_id'] ?? null) != 17 &&
            ($data['incidencia']['incidence_id'] ?? null) != 19 &&
            ($data['incidencia']['incidence_id'] ?? null) != 20
        ) {
            if (($data['incidencia']['requires_auth'] ?? 0) == 1 && ($data['incidencia']['approved_by'] ?? 0) > 1) {
                $codigo_asistencia = $data['incidencia']['incidence_id'];
            } else {
                $codigo_asistencia = 9;
            }

            if (($data['incidencia']['requires_auth'] ?? 0) != 1) {
                $codigo_asistencia = $data['incidencia']['incidence_id'];
            }
        }

        $c=0;
        $turno_horario_anterior = [];
        // ADELANTO DE TURNO
        if (( $data['incidencia']['incidence_id']?? null) == 20 ) {
            $turno_cumplido = $this->cumplio_turno( $data['employee_id'], $incidencia, $horario, $horario_incidencia);
            
            if( $data['incidencia']['tipo_incidencia'] == 'DESCANSO'){
                $codigo_asistencia = 20;
                if (($data['incidencia']['requires_auth'] ?? 0) == 1 && ($data['incidencia']['approved_by'] ?? 0) > 1) {
                    $codigo_asistencia = $codigo_asistencia;
                } else {
                    $codigo_asistencia = 9;
                }
            }else{
                if ( $turno_cumplido['cumplio'] == true) {
                    $codigo_asistencia = $codigo_asistencia;
                } elseif ($turno_cumplido['cumplio'] == false) {
                    
                    $turno_horario_anterior = $this->horario( $data['employee_id'], $incidencia['before_date'], 0);
                    $c = $codigo_asistencia;

                    // VALIDACION QUE LA FECHA DE ADELANTO NO HAYA SIDO DESCANSO
                    if( $turno_horario_anterior['nombre_horario'] == 'Descanso' && $turno_cumplido['horas_trabajadas'] >= 6 ){
                        $codigo_asistencia = $codigo_asistencia;
                    }else if( $turno_horario_anterior['nombre_horario'] == 'Descanso' && $turno_cumplido['horas_trabajadas'] == 0 ){
                        $codigo_asistencia = 67;
                    }
                    else if( ($turno_cumplido['horas_trabajadas'] ?? 0) == 0  ){
                        $codigo_asistencia = 68;
                    }else{
                        $codigo_asistencia = 67;
                    }
                }

            }

            if (($data['incidencia']['requires_auth'] ?? 0) == 1 && ($data['incidencia']['approved_by'] ?? 0) > 1) {
                $codigo_asistencia = $codigo_asistencia;
            } else {
                $codigo_asistencia = 9;
            }
        }

        // PENDIENTE DE REPONER TURNO
        if ( ($data['incidencia']['incidence_id'] ?? null) == 19 && ($data['incidencia']['tipo_incidencia'] ?? null) != 'TRABAJA') {
            $codigo_asistencia = 19;
            if (($data['incidencia']['requires_auth'] ?? 0) == 1 && ($data['incidencia']['approved_by'] ?? 0) > 1) {
                $codigo_asistencia = $codigo_asistencia;
            } else {
                $codigo_asistencia = 9;
            }

        } elseif ( ($data['incidencia']['incidence_id'] ?? null ) == 19 && ($data['incidencia']['tipo_incidencia'] ?? null) == 'TRABAJA') {

            $turno_cumplido = $this->cumplio_turno( $data['employee_id'], $incidencia, $horario, $horario_incidencia);

            if ($turno_cumplido['cumplio']== false) {

                $c = $codigo_asistencia;
                if( $codigo_asistencia == 9){
                    $codigo_asistencia = 68;
                }else{
                    $codigo_asistencia = 50;
                }
            }
            if (($data['incidencia']['requires_auth'] ?? 0) == 1 && ($data['incidencia']['approved_by'] ?? 0) > 1) {
                $codigo_asistencia = $codigo_asistencia;
            } else {
                $codigo_asistencia = 9;
            }
        }

        // TURNO NO INICIADO
        if ($horaEntradaHorario != 'Descanso') {
            if ($fecha == $fecha_actual) {
                if ($hora_actual < $tolAntesEntrada) {
                    $codigo_asistencia = 0;
                }
            } elseif ($fecha > $fecha_actual) {
                $codigo_asistencia = 0;
            }
        }

        // EMPLEADO AÚN NO INGRESABA
        $employee = DB::table('employees')
            ->select('entry_date')
            ->where('id', $data['employee_id'])
            ->first();

        if ($employee && !empty($employee->entry_date)) {
            $fecha_ingreso = date('Y-m-d', strtotime($employee->entry_date));
            $fecha_revisada = date('Y-m-d', strtotime($fecha));

            if ($fecha_revisada < $fecha_ingreso) {
                $codigo_asistencia = 0;
            }
        }

        $dias_semana = [
            '1' => 'monday',
            '2' => 'tuesday',
            '3' => 'wednesday',
            '4' => 'thursday',
            '5' => 'friday',
            '6' => 'saturday',
            '7' => 'sunday',
        ];

        $numero_dia = date('N', strtotime($fecha));
        $day_status = $dias_semana[$numero_dia] . '_status';
        $data_status = $dias_semana[$numero_dia] . '_data';

        $data['data_status'] = $data_status;
        $data['day_status'] = $day_status;
        $data['codigo_asistencia'] = $codigo_asistencia;
        $data['cumplio_turno'] = $turno_cumplido;
        $data['turno_cumplido'] = $turno_cumplido;
        $data['c'] = $c;
        $data['turno_horario_anterior'] = $turno_horario_anterior;

        // PRIMA DOMINICAL
        if ($day_status == 'saturday_status' && $codigo_asistencia == 1 && ($horario['es_turno_nocturno'] ?? false) == true) {
            $data['sunday_premium'] = 1;
        }

        if ($day_status == 'sunday_status' && $codigo_asistencia == 1) {
            $schedule_id = (int) ($horario['schedule_id'] ?? 0);

            if (!in_array($schedule_id, [8, 13, 15, 35], true)) {
                $data['sunday_premium'] = 1;
            } else {
                $data['sunday_premium'] = 0;
            }

            $data['schedule_id'] = $schedule_id;
        }
    }

    public function crearRegistro(int $employee_id, string $fecha){
        
        $week_number = (int) date("W", strtotime($fecha));
        $week_year   = date("Y", strtotime($fecha));
        return [
            "id_weekly" => 1,
            "week_number" => $week_number,
            "week_year" => $week_year,
        ];
    }

    public function revisarAsistencia(int $employee_id, string $fecha) {

        // INCIDENCIAS
        $incidencia = $this->incidencias($employee_id, $fecha );
        
        // HORARIO ORDINARIO
        $horario = $this->horario($employee_id, $fecha, 0);

        // HORARIO DE CAMBIO DE TURNO
        if( ($incidencia['incidence_id'] ?? null )== 17 ){
            $horario = $this->horario($employee_id, $fecha, $incidencia['schedule_id']);
        }

        if($horario['estatus']== 'error' ){
            return [
                'estatus' =>'error',
                'employee_id' => $employee_id,
                'message' =>'No existe un horario',
                'date' => $fecha
            ];
        }


        // HORARIO INCIDENCIA
        if( $incidencia ){
            if( $incidencia['tipo_incidencia'] !="INCIDENCIA_NORMAL"){
                $horarioIncidencia = $this->horario($employee_id, $fecha, $incidencia['schedule_id']);
            }
        }
        
        // CHECADAS
        $hikcentral = $this->hikcentral($employee_id, $fecha, $horario );

        // CHECADAS POR TURNO 
        $checadas = $this->checadas($employee_id, $fecha, $horario, $incidencia );

        // CREAR REGISTRO 
        $data_weekly = $this->crearRegistro($employee_id, $fecha );


        $data = [
            "estatus" => "OK",
            "employee_id" => $employee_id,
            "fecha" => $fecha,
            "horario" => $horario,
            "checadas" => $checadas,
            "incidencia" => $incidencia,
            "data_weekly" => $data_weekly,
            "hikcentral" => $hikcentral,
            "horario_incidencia" => $horarioIncidencia ?? null
        ];

         // VALIDACION ASISTENCIA
        $validarAsistencia = $this->validarAsistencia($data);
        $data['asistencia'] = $validarAsistencia;

        $employee_id   = $data['employee_id'];
        $week_number   = $data['data_weekly']['week_number'];
        $week_year     = $data['data_weekly']['week_year'];
        $incidence_id  = $data['codigo_asistencia'];
        $campo_status  = $data['day_status'];
        $campo_data    = $data['data_status'];

        if ($incidence_id == 1) {
            $tiempo_extra_doble  = $data['horario']['tiempo_extra_doble'];
            $tiempo_extra_triple = $data['horario']['tiempo_extra_triple'];
        } else {
            $tiempo_extra_doble  = 0;
            $tiempo_extra_triple = 0;
        }

        if ($incidence_id == 19) {

            if (($data['cumplio_turno']['cumplio'] ?? false) == true) {
                $tiempo_extra_doble  = $data['horario']['tiempo_extra_doble'];
                $tiempo_extra_triple = $data['horario']['tiempo_extra_triple'];
            } else {
                $tiempo_extra_doble  = 0;
                $tiempo_extra_triple = 0;
            }
        }

        $eventos = $this->eventos($fecha, $horario );
        $data['eventos'] = $eventos;

        if( $eventos ){
            if( !$incidencia ){
                $incidence_id = 42;
            }
        }

        $datos = json_encode([
            "Turno" => $data['horario']['nombre_turno'],
            "Horario" => $data['horario']['nombre_horario'],
            "Entrada" => $data['horario']['hora_entrada'],
            "Salida" => $data['horario']['hora_salida'],
            "Horas dobles" => $tiempo_extra_doble,
            "Horas triples" => $tiempo_extra_triple,
            "Nocturno" => $data['horario']['es_turno_nocturno'],
            "Checadas" => $hikcentral,
            "sunday_premium" => $data['sunday_premium'] ?? 0,
        ], JSON_UNESCAPED_UNICODE);

        
        $updateData = [
            $campo_status => $incidence_id,
            $campo_data   => $datos,
            'updated_at'  => now(),
        ];

        DB::table('weekly_assistances')
            ->where('employee_id', $employee_id)
            ->where('week_number', $week_number)
            ->where('week_year', $week_year)
            ->whereNull('deleted_at')
            ->update($updateData);

        $asistencias =$this->asistencias( $data );
        
        return $data;
    }

    public function asistencias(array &$data)
    {
        $employee_id = $data['employee_id'];
        $date = $data['fecha'];
        $week_number = $data['data_weekly']['week_number'];
        $week_year = $data['data_weekly']['week_year'];
        $schedule_id = $data['horario']['schedule_id'];
        $shift_role_id = $data['horario']['shift_role_id'];
        $incidence_id = $data['codigo_asistencia'];
        $employee_incidence_id = $data['incidencia']['id'] ?? 0;
        $day = date('d', strtotime($date));
        $entrance_time = $data['checadas']['primera_checada'] ?? null;
        $leave_time = $data['checadas']['ultima_checada'] ?? null;

        if ($schedule_id == 'Descanso') {
            $schedule_id = 1;
        }

        $asistencia = DB::table('assistances')
            ->select('id')
            ->where('employee_id', $employee_id)
            ->where('date', $date)
            ->whereNull('deleted_at')
            ->first();

        $id_asistencia = $asistencia->id ?? null;

        $empleado = DB::table('employees')
            ->select('branch_office_id')
            ->where('id', $employee_id)
            ->first();

        $branch_office_id = $empleado->branch_office_id ?? null;

        if ($id_asistencia) {
            DB::table('assistances')
                ->where('id', $id_asistencia)
                ->update([
                    'entrance_time' => $entrance_time,
                    'leave_time' => $leave_time,
                    'week_day' => $day,
                    'week_number' => $week_number,
                    'week_year' => $week_year,
                    'incidence_id' => $incidence_id,
                    'employee_incidence_id' => $employee_incidence_id,
                    'branch_office_id' => $branch_office_id,
                    'shift_role_id' => $shift_role_id,
                    'schedule_id' => $schedule_id,
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);

            return $id_asistencia;
        } else {
            return DB::table('assistances')->insertGetId([
                'date' => $date,
                'entrance_time' => $entrance_time,
                'leave_time' => $leave_time,
                'week_day' => $day,
                'week_number' => $week_number,
                'week_year' => $week_year,
                'incidence_id' => $incidence_id,
                'employee_incidence_id' => $employee_incidence_id,
                'employee_id' => $employee_id,
                'branch_office_id' => $branch_office_id,
                'shift_role_id' => $shift_role_id,
                'schedule_id' => $schedule_id,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }
    public function eventos( string $date, array $horario): ?array
    {
        $fecha_original = $date;
        $fecha_busqueda_evento = $date;

        if ($horario['es_turno_nocturno'] == true) {
            $fecha_busqueda_evento = date('Y-m-d', strtotime("$date +1 day"));
        }

        $week_number = date('W', strtotime($fecha_original));
        $week_year   = date('Y', strtotime($fecha_original));

        $data = DB::table('events')
            ->where('holiday', 1)
            ->whereNull('deleted_at')
            ->whereDate('start_date', $fecha_busqueda_evento)
            ->first();

        if (!$data) {
            return null;
        }

        $dias_semana = [
            '1' => 'monday',
            '2' => 'tuesday',
            '3' => 'wednesday',
            '4' => 'thursday',
            '5' => 'friday',
            '6' => 'saturday',
            '7' => 'sunday',
        ];

        $numero_dia = date('N', strtotime($fecha_original));
        $day_status = $dias_semana[$numero_dia] . '_status';
        $data_status = $dias_semana[$numero_dia] . '_data';

        return [
            "evento_en_fecha_original" => $fecha_original,
            "evento_aplicado_en" => $fecha_busqueda_evento,
            "week_number" => $week_number,
            "week_year" => $week_year,
            "columna_actualizada" => $day_status,
            "data_status" => $data_status,
            "event" => (array) $data,
        ];
    }
}

