<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dashboard extends Model
{
    public static function metrics()
    {
        $total_employees = DB::select("SELECT COUNT(id) as total_employees FROM employees WHERE status != 'termination';");
        $new_employees = DB::select("SELECT COUNT(*) AS new_employees
                                    FROM employees
                                    WHERE status = 'entry'
                                    AND entry_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                    AND entry_date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01');");

        $termination_employees = DB::select("SELECT COUNT(*) AS termination_employees
                                            FROM employees
                                            WHERE status = 'termination'
                                            AND termination_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                            AND termination_date <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01');");

        $assistances = DB::select("SELECT COUNT(*) AS assistances
                                    FROM weekly_assistances
                                    WHERE week_from = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                    AND CASE DAYOFWEEK(CURDATE())
                                        WHEN 2 THEN monday_status
                                        WHEN 3 THEN tuesday_status
                                        WHEN 4 THEN wednesday_status
                                        WHEN 5 THEN thursday_status
                                        WHEN 6 THEN friday_status
                                        WHEN 7 THEN saturday_status
                                        WHEN 1 THEN sunday_status
                                    END IN (1, 30, 31, 33, 24, 25, 26);"); 
        $complete_assistances = DB::select("SELECT COUNT(*) AS complete_assistances
                                    FROM weekly_assistances
                                    WHERE week_from = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                    AND CASE DAYOFWEEK(CURDATE())
                                        WHEN 2 THEN monday_status
                                        WHEN 3 THEN tuesday_status
                                        WHEN 4 THEN wednesday_status
                                        WHEN 5 THEN thursday_status
                                        WHEN 6 THEN friday_status
                                        WHEN 7 THEN saturday_status
                                        WHEN 1 THEN sunday_status
                                    END IN (1, 30, 31, 33, 24, 25, 26);"); 
        $abstences = DB::select("SELECT COUNT(*) AS abstences
                                    FROM weekly_assistances
                                    WHERE week_from = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                    AND CASE DAYOFWEEK(CURDATE())
                                        WHEN 2 THEN monday_status
                                        WHEN 3 THEN tuesday_status
                                        WHEN 4 THEN wednesday_status
                                        WHEN 5 THEN thursday_status
                                        WHEN 6 THEN friday_status
                                        WHEN 7 THEN saturday_status
                                        WHEN 1 THEN sunday_status
                                    END = 9;");
        $late = DB::select("SELECT COUNT(*) AS late
                            FROM weekly_assistances
                            WHERE week_from = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                            AND CASE DAYOFWEEK(CURDATE())
                                WHEN 2 THEN monday_status
                                WHEN 3 THEN tuesday_status
                                WHEN 4 THEN wednesday_status
                                WHEN 5 THEN thursday_status
                                WHEN 6 THEN friday_status
                                WHEN 7 THEN saturday_status
                                WHEN 1 THEN sunday_status
                            END = 52;");
        $plant_stats = DB::select("SELECT branch_offices.code AS branch_office, COUNT(*) AS total_employees
                                    FROM employees
                                    INNER JOIN branch_offices ON branch_office_id = branch_offices.id
                                    GROUP BY branch_office_id
                                    ORDER BY total_employees DESC;");

        return [
            'total_employees' => $total_employees[0]->total_employees,
            'new_employees' => $new_employees[0]->new_employees,
            'termination_employees' => $termination_employees[0]->termination_employees,
            'assistances' => $assistances[0]->assistances,
            'complete_assistances' => $complete_assistances[0]->complete_assistances,
            'abstences' => $abstences[0]->abstences,
            'late' => $late[0]->late,
            'plant_stats' => $plant_stats,
        ];
    }
}
