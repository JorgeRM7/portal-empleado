<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmployeeSearch extends Model
{
    public static function searchEmployee($employee_id){
        $sql_incidecnias = "SELECT
            	employees.id,
            	employees.full_name,
            	incidences.name AS incidencia,
            	employee_incidences.document_number,
            	employee_incidences.validity_from,
            	employee_incidences.validity_to,
            	employee_incidences.days,
            	employee_incidences.comment,
            	(SELECT name FROM users WHERE id = employee_incidences.approved_by) AS approved_by
            FROM employee_incidences
            INNER JOIN incidences ON incidences.id = employee_incidences.incidence_id
            INNER JOIN employees ON employees.id = employee_incidences.employee_id
            WHERE employee_incidences.employee_id ='$employee_id'
        ";

        $incidencias =  DB::select($sql_incidecnias);

        $sql_vacaciones = "SELECT
                edv.id,
                edv.employee_id,
                e.full_name,
                edv.amount,
                edv.seniority,
                edv.date,
                edv.branch_office_id
            FROM employee_day_vacations AS edv
            LEFT JOIN employees AS e ON edv.employee_id = e.id
            WHERE e.deleted_at IS NULL
            AND edv.employee_id IS NOT NULL
            AND e.full_name IS NOT NULL
            AND edv.deleted_at IS NULL
            AND edv.employee_id IN($employee_id)
        ";

        $vacaciones =  DB::select($sql_vacaciones);

        $sql_asistencia_semanal="SELECT
                wa.week_number,
                MAX((SELECT code FROM incidences WHERE id = wa.monday_status))     AS lunes,
                MAX((SELECT code FROM incidences WHERE id = wa.tuesday_status))    AS martes,
                MAX((SELECT code FROM incidences WHERE id = wa.wednesday_status))  AS miercoles,
                MAX((SELECT code FROM incidences WHERE id = wa.thursday_status))   AS jueves,
                MAX((SELECT code FROM incidences WHERE id = wa.friday_status))     AS viernes,
                MAX((SELECT code FROM incidences WHERE id = wa.saturday_status))   AS sabado,
                MAX((SELECT code FROM incidences WHERE id = wa.sunday_status))     AS domingo,

                MAX((SELECT color FROM incidences WHERE id = wa.monday_status))     AS color_lunes,
                MAX((SELECT color FROM incidences WHERE id = wa.tuesday_status))    AS color_martes,
                MAX((SELECT color FROM incidences WHERE id = wa.wednesday_status))  AS color_miercoles,
                MAX((SELECT color FROM incidences WHERE id = wa.thursday_status))   AS color_jueves,
                MAX((SELECT color FROM incidences WHERE id = wa.friday_status))     AS color_viernes,
                MAX((SELECT color FROM incidences WHERE id = wa.saturday_status))   AS color_sabado,
                MAX((SELECT color FROM incidences WHERE id = wa.sunday_status))     AS color_domingo,

                MAX((SELECT name FROM incidences WHERE id = wa.monday_status))     AS name_lunes,
                MAX((SELECT name FROM incidences WHERE id = wa.tuesday_status))    AS name_martes,
                MAX((SELECT name FROM incidences WHERE id = wa.wednesday_status))  AS name_miercoles,
                MAX((SELECT name FROM incidences WHERE id = wa.thursday_status))   AS name_jueves,
                MAX((SELECT name FROM incidences WHERE id = wa.friday_status))     AS name_viernes,
                MAX((SELECT name FROM incidences WHERE id = wa.saturday_status))   AS name_sabado,
                MAX((SELECT name FROM incidences WHERE id = wa.sunday_status))     AS name_domingo,

                MAX(wa.id) AS id_original,
                wa.*

            FROM weekly_assistances wa
            WHERE wa.employee_id = '$employee_id'
            GROUP BY wa.week_number, wa.week_year
            ORDER BY wa.week_number DESC;
            ";

        $asistencia_semanal =  DB::select($sql_asistencia_semanal);

        $sql_eficiencias = "SELECT
            	employee_efficiencies_v2.id,
            	branch_offices.code,
            	employee_efficiencies_v2.employee_id,
            	employee_efficiencies_v2.efficiency,
            	employee_efficiencies_v2.month,
            	employee_efficiencies_v2.year,
            	employee_efficiencies_v2.production,
            	employee_efficiencies_v2.goal,
            	employee_efficiencies_v2.branch_office_id
            FROM employee_efficiencies_v2
            LEFT JOIN branch_offices ON employee_efficiencies_v2.branch_office_id = branch_offices.id
            WHERE employee_efficiencies_v2.employee_id ='$employee_id' ORDER BY employee_efficiencies_v2.year DESC, employee_efficiencies_v2.month DESC
        ";

        $eficiencias = DB::select($sql_eficiencias);

        $employee_data = "WITH LastStatus AS (
                        SELECT
                            es.employee_id,
                            esr.name AS reason_name,
                            es.content AS observation,
                            ROW_NUMBER() OVER (PARTITION BY es.employee_id ORDER BY es.created_at DESC) as ranking
                        FROM employee_statuses es
                        JOIN employee_status_reasons esr ON es.reason_id = esr.id
                        WHERE es.status = 'termination'
                        AND es.employee_id = $employee_id
                        AND esr.type = 'BAJA'
                    ),
                    RehireablesList AS (
                        SELECT DISTINCT employee_id
                        FROM employee_rehireables
                        WHERE employee_id = $employee_id
                    )
                    SELECT
                        e.*,
                        td.tax_id,
                        bo.code AS branch_office_name,
                        p.name AS position_name,
                        d.name AS department_name,
                        c.name AS companies_name,
                        a.postal_code,
                        CASE
                            WHEN ls.employee_id IS NOT NULL
                                AND e.status = 'termination'
                                AND e.rehireable = 0
                                AND rl.employee_id IS NULL
                                AND ls.reason_name NOT IN ('ALTAS','TRASPASO','REINGRESO','NUEVO INGRESO')
                            THEN 0 ELSE 1
                        END AS rehireable_calc,
                        ls.reason_name AS termination_reason,
                        ls.observation AS termination_observation
                    FROM employees e
                    INNER JOIN branch_offices bo ON e.branch_office_id = bo.id
                    INNER JOIN positions p ON p.id = e.position_id
                    INNER JOIN departments d ON d.id = e.department_id
                    LEFT JOIN addresses a ON a.addressable_id = e.id
                    LEFT JOIN tax_data td ON e.id = td.owner_id
                    LEFT JOIN companies c ON c.id = e.company_id
                    LEFT JOIN LastStatus ls ON ls.employee_id = e.id AND ls.ranking = 1
                    LEFT JOIN RehireablesList rl ON rl.employee_id = e.id
                    WHERE e.id = $employee_id";

        $empleado = DB::select($employee_data);

        $sql_recibos = "
            SELECT
                branch_offices.code AS planta,
                employees.id AS numero_nomina,
                employees.full_name AS nombre_empleado,
                payroll_invoices.week AS semana,
                payroll_invoices.year,
                payroll_invoices.extra,
                payroll_invoices.pdf_path,
                payroll_invoices.id,
                payroll_invoices.estatus_correo,
                payroll_invoices.error_correo,
                payroll_invoices_types.name AS tipo_recibo
            FROM `payroll_invoices`
            INNER JOIN branch_offices ON branch_offices.id = payroll_invoices.branch_office_id
            INNER JOIN employees ON employees.id = payroll_invoices.employee_id
            LEFT JOIN payroll_invoices_types ON payroll_invoices_types.id = payroll_invoices.payroll_invoices_type_id
            WHERE payroll_invoices.employee_id = $employee_id
        ";
        $recibosNomina = DB::select($sql_recibos);

        $sql_shift_roles = "
            SELECT
                shift_roles.name,
                employee_shift_roles.start_date,
                employee_shift_roles.end_date,
                employee_shift_roles.active
            FROM employee_shift_roles
            INNER JOIN shift_roles ON shift_roles.id = employee_shift_roles.shift_role_id
            INNER JOIN employees ON employees.id = employee_shift_roles.employee_id
            WHERE employee_shift_roles.employee_id = $employee_id
        ";
        $shift_roles = DB::select($sql_shift_roles);

        $sql_role_cycles = "
            SELECT
                employee_shift_role_cycles.started_at,
                employee_shift_role_cycles.ends_at,
                shift_roles.name,
                schedules.name,
                schedules.entry_time,
                schedules.leave_time
            FROM employee_shift_role_cycles
            INNER JOIN schedules ON schedules.id = employee_shift_role_cycles.schedule_id
            INNER JOIN shift_roles ON shift_roles.id = employee_shift_role_cycles.shift_role_id
            WHERE employee_shift_role_cycles.employee_id = $employee_id
        ";
        $role_cycles = DB::select($sql_role_cycles);

        $sql_compensations = "
            SELECT
                ec.id AS ID,
                e.full_name AS Empleado,
                ec.compensation AS Compensacion,
                ec.piece_work AS Destajo,
                ec.extra_compensation AS ExtraCompensacion,
                ec.transport AS Transporte,
                ec.week_number AS Semana,
                ec.week_year AS AñoSemana,
                ec.comment AS Observaciones,
                d.name AS Departamento,
                p.name AS Posicion,
                p.compensation AS CompePuesto,
                aprovador.name AS Aprovado,
                ec.approved_at AS FechaAprovado
            FROM
                employee_compensation ec
            LEFT JOIN
                employees e ON ec.employee_id = e.id
            LEFT JOIN
                departments d ON e.department_id= d.id
            LEFT JOIN
                positions p ON e.position_id = p.id
            LEFT JOIN
                users aprovador ON ec.approved_by = aprovador.id
            WHERE ec.employee_id = $employee_id
            ORDER BY ec.id DESC
        ";
        $compensations = DB::select($sql_compensations);

        $sql_extra_time = "
            SELECT
                eo.id AS ID,
                eo.employee_id AS EmpleadoID,
                e.full_name AS Empleado,
                eo.date AS Fecha,
                s.name AS Horario,
                eo.overtimes AS TiemposExtra,
                eo.untimely AS Extemporaneo,
                eo.approved_by AS Aprovado,
                eo.declined_by AS Declinado
            FROM
                employee_overtimes eo
            JOIN
                employees e ON eo.employee_id = e.id
            LEFT JOIN
                schedules s ON eo.schedule_id = s.id
            WHERE eo.employee_id = $employee_id
            ORDER BY eo.id DESC
        ";
        $extra_time = DB::select($sql_extra_time);

        $sql_status_history = "
            SELECT
                es.id,
                es.employee_id,
                e.full_name AS employee_name,
                esr.name AS reason_name,
                es.status,
                es.date,
                es.content,
                u.name AS user_name,
                es.values AS planta,
                es.created_at,
                es.updated_at,
                e.branch_office_id
            FROM employee_statuses es
            LEFT JOIN employees e ON es.employee_id = e.id
            LEFT JOIN employee_status_reasons esr ON es.reason_id = esr.id
            LEFT JOIN users u ON es.user_id = u.id
            WHERE es.employee_id = $employee_id
            ORDER BY `values` ASC
        ";
        $status_history = DB::select($sql_status_history);

        $sql_tiempo_x_tiempo = "
            SELECT
                eo.id,
                e.full_name AS employee_name,
                e.department_id,
                eo.employee_id,
                eo.date,
                s.name AS schedule_name,
                eo.schedule_id,
                eo.hours,
                eo.approved_at,
                eo.approved_by,
                u.name AS approved_by_name,
                eo.validated_at,
                eo.declined_at,
                eo.declined_by,
                ud.name AS declined_by_name,
                eo.branch_office_id,
                eo.comment,
                eo.week_number,
                eo.week_year,
                eo.deleted_at
            FROM
                employee_time_by_time AS eo
            LEFT JOIN
                employees e ON eo.employee_id = e.id
            LEFT JOIN
                users u ON eo.approved_by = u.id
            LEFT JOIN
                users ud ON eo.declined_by = ud.id
            LEFT JOIN
                schedules s ON eo.schedule_id = s.id
            WHERE
                eo.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND eo.approved_at IS NOT NULL
                AND eo.employee_id IN ($employee_id)
            ORDER BY `values` ASC
        ";
        $tiempo_x_tiempo = DB::select($sql_tiempo_x_tiempo);

        return [
            'incidencias'        => $incidencias,
            'vacaciones'         => $vacaciones,
            'asistencia_semanal' => $asistencia_semanal,
            'eficiencias'        => $eficiencias,
            'empleado'           => $empleado,
            "roles_turnos"       => $shift_roles,
            "ciclos_turno"       => $role_cycles,
            "compensaciones"     => $compensations,
            "tiempo_extra"       => $extra_time,
            "historial_estados"  => $status_history,
            "recibos_nomina"     => $recibosNomina,
            "tiempo_x_tiempo"     => $tiempo_x_tiempo,

        ];
    }
}
