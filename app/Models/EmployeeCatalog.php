<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmployeeCatalog extends Model
{

    use SoftDeletes;

    protected $table = 'employees';

    public static function downloadAll($data = []){
        $ids_empleados = $data['ids_exportar'];

        $sql = "
            SELECT
                e.id AS 'Clave Empleado',
                e.name AS Nombre,
                e.surname AS 'Apellido Paterno',
                e.mother_surname AS 'Apellido Materno',
                e.personal_phone AS 'Telefono Personal',
                e.birthday AS 'Fecha Nacimiento',
                e.department_id AS 'Clave departamento',
                e.position_id AS 'Clave puesto',
                g.name AS Genero,
                s.id AS 'Entidad Nacimiento',
                e.dni AS CURP,
                e.health_id AS NSS,
                e.email AS Correo,
                addr.street AS Calle,
                addr.internal_number AS 'Numero Interior',
                addr.external_number AS 'Numero Exterior',
                st_addr.name AS Estado,
                c.name AS Ciudad,
                l.name AS Colonia,
                addr.postal_code AS 'Codigo Postal',
                td.tax_id AS RFC,
                s.code_3 AS 'Clave de entidad',
                td.postal_code AS 'Codigo Postal Fiscal',
                c.state_id AS 'Entidad Federativa',
                pd.account_number AS 'Numero Cuenta',
                pd.account_card AS 'Numero Tarjeta',
                pd.account_code AS 'Clabe Interbancaria',
                pd.salary AS SDI,
                pd.daily_salary AS 'Salario Diario',
                b.name AS 'Banco',
                pm.name AS 'Metodo Pago',
                e.entry_date AS 'Fecha Ingreso',
                e.transfer_date AS 'Fecha Traspaso',
                e.termination_date AS 'Fecha Terminacion',
                CASE e.status
                    WHEN 'entry' THEN 'Alta'
                    WHEN 'termination' THEN 'Baja'
                    WHEN 'change' THEN 'Traspaso'
                    WHEN 'reentry' THEN 'Reingreso'
                    ELSE e.status
                END AS 'Estado Empleado',
                e.employee_parent_id AS 'Jefe Inmediato',
                e.employee_parent_email AS 'Correo Jefe Inmediato',
                d.name AS Departamento,
                pos.name AS Puesto,
                bo.code AS Planta,
                e.company_phone AS 'Telefono Empresa',
                eb.lista_prestaciones AS Prestaciones,
                e.additional_info,
                JSON_UNQUOTE(JSON_EXTRACT(b.meta, '$.code')) AS 'Clave banco operador',
                JSON_UNQUOTE(JSON_EXTRACT(e.additional_info, '$.blood_type')) AS 'Tipo de sangre',
                JSON_UNQUOTE(JSON_EXTRACT(e.additional_info, '$.days')) AS 'Dias duracion'
            FROM employees e
            LEFT JOIN genders g ON g.id = e.gender_id
            LEFT JOIN states s ON s.id  = e.state_id
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions pos ON pos.id = e.position_id
            LEFT JOIN branch_offices bo ON bo.id = e.branch_office_id
            LEFT JOIN (
                SELECT be.employee_id, GROUP_CONCAT(bn.name) AS lista_prestaciones
                FROM benefit_employee be
                JOIN benefits bn ON bn.id = be.benefit_id
                GROUP BY be.employee_id
            ) eb ON eb.employee_id = e.id
            LEFT JOIN addresses addr ON addr.addressable_id = e.id
            LEFT JOIN states st_addr ON st_addr.id = addr.state_id
            LEFT JOIN cities c ON c.id = addr.city_id
            LEFT JOIN locations l ON l.id = addr.location_id
            LEFT JOIN tax_data td ON td.owner_id = e.id
            LEFT JOIN payment_data pd ON pd.owner_id = e.id
            LEFT JOIN banks b ON b.id = pd.bank_id
            LEFT JOIN payment_methods pm ON pm.id = pd.payment_method_id
        ";

        if (!empty($ids_empleados)) {

            // si viene como objeto o nested
            if (isset($ids_empleados['data'])) {
                $ids_empleados = $ids_empleados['data'];
            }

            // asegurar array plano
            $ids_empleados = array_values((array)$ids_empleados);

            $ids = implode(",", array_map('intval', $ids_empleados));

            $sql .= " WHERE e.id IN ($ids)";
        }

        return DB::cursor($sql);

    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function paymentData()
    {
        return $this->hasOne(PaymentData::class, 'owner_id');
    }

    public function state()
    {
        // return $this->hasOne(State::class, 'id');
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

}
