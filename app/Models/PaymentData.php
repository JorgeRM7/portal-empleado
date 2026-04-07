<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentData extends Model
{
    use HasFactory;

    protected $table = 'payment_data';

    protected $fillable = [
        'account_number',
        'account_card',
        'account_code',
        'salary',
        'daily_salary',
        'meta',
        'bank_id',
        'salary_type_id',
        'payment_method_id',
        'external_account_id',
        'owner_id',
        'owner_type'
    ];

    protected $casts = [
        'salary'       => 'float',
        'daily_salary' => 'float',
        'meta'         => 'array'
    ];

   

    public function owner()
    {
        return $this->morphTo();
    }

    // public function bank()
    // {
    //     return $this->belongsTo(Bank::class, 'bank_id');
    // }

    // public function salaryType()
    // {
    //     return $this->belongsTo(SalaryType::class, 'salary_type_id');
    // }

    // public function paymentMethod()
    // {
    //     return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    // }
}
