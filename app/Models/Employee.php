<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'id',
        'code',
        'external_id',
        'name',
        'surname',
        'mother_surname',
        'full_name',
        'email',
        'company_email',
        'personal_phone',
        'company_phone',
        'status',
        'dni',
        'health_id',
        'company_id',
        'state_id',
        'profile_photo_path',
        'department_id',
        'position_id',
        'employee_parent_id',
        'employee_parent_email',
        'shift_role_id',
        'gender_id',
        'user_id',
        'branch_office_id',
        'birthday',
        'employee_policy_id',
        'candidate_id',
        'current_branch_office_machine_id',
        'area_id',
        'entry_date',
        'termination_date',
        'reentry_date',
        'transfer_date',
        'branch_office_location_id',
        'account_id',
        'file_cv',
        'rehireable',
        'terms_condition',
        'classification_id',
        'additional_info'
    ];

    protected $casts = [
        'birthday' => 'date',
        'entry_date' => 'date',
        'termination_date' => 'date',
        'reentry_date' => 'date',
        'transfer_date' => 'date',
        'rehireable' => 'boolean',
        'terms_condition' => 'boolean',
        'additional_info' => 'array'
    ];




    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function paymentData()
    {
        return $this->hasOne(PaymentData::class, 'owner_id');
    }

    public function taxData()
    {
        return $this->hasOne(TaxData::class, 'owner_id');
    }

    public function state()
    {
        return $this->hasOne(State::class, 'id');
    }

    public function benefits()
    {
        return $this->belongsToMany(
            Benefit::class,
            'benefit_employee',
            'employee_id',
            'benefit_id'
        );
    }

    public function parents()
    {
        return $this->belongsToMany(
            Employee::class,
            'employees',
            'id',
            'employee_parent_id'
        );
    }

    public static function noRehirable($filters)
    {
        return Employee::select(
            'employees.id',
            'employees.full_name as employee',
            'branch_offices.name as branchOffice',
            DB::raw("DATE_FORMAT(employees.entry_date, '%d-%m-%Y') as entry_date_formatted"),
            DB::raw("DATE_FORMAT(employees.termination_date, '%d-%m-%Y') as termination_date_formatted"),
            'employee_statuses.content',
            'employee_status_reasons.name as termination_reason'
        )
        ->join('branch_offices', 'branch_offices.id', '=', 'employees.branch_office_id')
        ->join('employee_statuses', 'employee_statuses.employee_id', '=', 'employees.id')
        ->join('employee_status_reasons', 'employee_status_reasons.id', '=', 'employee_statuses.reason_id')
        ->where('employees.status', 'termination')
        ->where('employees.rehireable', 0)
        ->where('employee_status_reasons.type', 'BAJA')
        ->when($filters['planta'], function ($query, $planta) {
            return $query->whereIn('employees.branch_office_id', (array)$planta);
        })
        ->when(!empty($filters['employees']), function ($query) use ($filters) {
            return $query->whereIn('employees.id', $filters['employees']);
        })
        ->when($filters['ingreso_desde'] && $filters['ingreso_hasta'], function ($query) use ($filters) {
            return $query->whereBetween('employees.entry_date', [$filters['ingreso_desde'], $filters['ingreso_hasta']]);
        })
        ->when($filters['termino_desde'] && $filters['termino_hasta'], function ($query) use ($filters) {
            return $query->whereBetween('employees.termination_date', [$filters['termino_desde'], $filters['termino_hasta']]);
        })
        ->get();
    }

}
