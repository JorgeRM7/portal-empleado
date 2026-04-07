<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeBlockIncidents extends Model
{
    protected $table = 'employee_incidences_week_blocked';

    public static function index($plantaId)
    {
        // Fecha actual
        $fecha  = Carbon::now();
        $semana = (int) $fecha->weekOfYear;
        $anio   = (int) $fecha->year;

        $sql_semana = "
            SELECT id, estatus
            FROM employee_incidences_week_blocked
            WHERE week = {$semana}
            AND year = {$anio}
            AND branch_office_id = {$plantaId}
        ";

        $registrosSemana = DB::select($sql_semana);

        foreach ($registrosSemana as $item) {
            if ($item->estatus == 0) {
                $sql_update = "
                    UPDATE employee_incidences_week_blocked
                    SET estatus = 1
                    WHERE id = {$item->id}
                ";

                DB::update($sql_update);
            }
        }

        $sql = "
            SELECT eib.*, bo.code
            FROM employee_incidences_week_blocked eib
            INNER JOIN branch_offices bo
                ON bo.id = eib.branch_office_id
            WHERE eib.deleted_at IS NULL
            AND eib.branch_office_id = {$plantaId}
        ";

        return DB::select($sql);
    }

    public static function index_filter($filtros = []) {
        try {
            $filtro_planta = $filtros['plantas'] ?? [];
            $filtro_semana  = $filtros['semana'] ?? null;
            $filtro_estatus = $filtros['estatus'] ?? null;

            $sql = "SELECT eib.*, bo.code
                    FROM employee_incidences_week_blocked eib
                    INNER JOIN branch_offices bo ON bo.id = eib.branch_office_id
                    WHERE eib.deleted_at IS NULL";

            // Filtro de Plantas
            if (is_array($filtro_planta) && count($filtro_planta) > 0) {
                $ids = implode(',', array_map('intval', $filtro_planta));
                $sql .= " AND eib.branch_office_id IN ($ids)";
            }

            // Filtro de Semana
            if (!empty($filtro_semana) && str_contains($filtro_semana, '-W')) {
                // Explode divide el string en: [0] => "2026", [1] => "W08"
                $partes = explode('-W', $filtro_semana);
                $anio_extraido   = (int)$partes[0];
                $semana_extraida = (int)$partes[1];

                $sql .= " AND eib.year = {$anio_extraido} AND eib.week = {$semana_extraida}";
            }

            // Filtro de Estatus (0 o 1)
            if (!is_null($filtro_estatus) && $filtro_estatus !== '') {
                $sql .= " AND eib.estatus = " . (int)$filtro_estatus;
            }

            return DB::select($sql);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function updateStatus($id, $estatus)
    {
        $idRegistro = (int) $id;
        $nuevoEstatus = (int) $estatus;

        $sql = "
            UPDATE employee_incidences_week_blocked
            SET estatus = {$nuevoEstatus},
                updated_at = NOW()
            WHERE id = {$idRegistro}
        ";

        return DB::update($sql);
    }

    public static function getExistingPlants($year, $branchIds)
    {
        $ids = implode(',', array_map('intval', $branchIds));

        $sql = "
            SELECT DISTINCT bo.code
            FROM employee_incidences_week_blocked eib
            INNER JOIN branch_offices bo ON bo.id = eib.branch_office_id
            WHERE eib.year = {$year}
            AND eib.branch_office_id IN ({$ids})
            AND eib.deleted_at IS NULL
        ";

        return DB::select($sql);
    }

    public static function saveBlock($year, $branchIds)
    {
        $hoy = date('Y-m-d H:i:s');

        foreach ($branchIds as $idPlanta) {
            // Determinar si el año tiene 52 o 53 semanas ISO
            $date = new \DateTime();
            $date->setISODate($year, 53);
            $totalSemanas = ($date->format("W") === "53" ? 53 : 52);

            for ($semana = 1; $semana <= $totalSemanas; $semana++) {
                $inicio_semana = new \DateTime();
                $inicio_semana->setISODate($year, $semana, 1);

                $fin_semana = clone $inicio_semana;
                $fin_semana->modify('+6 days');

                $fecha_i = $inicio_semana->format('Y-m-d');
                $fecha_f = $fin_semana->format('Y-m-d');

                $sql = "
                    INSERT INTO employee_incidences_week_blocked
                    (week, year, estatus, branch_office_id, start_week, end_week, created_at, updated_at)
                    VALUES
                    ({$semana}, {$year}, 0, {$idPlanta}, '{$fecha_i}', '{$fecha_f}', '{$hoy}', '{$hoy}')
                ";

                DB::insert($sql);
            }
        }
    }
}
