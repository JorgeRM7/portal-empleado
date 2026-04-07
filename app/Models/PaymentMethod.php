<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'description',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];


    public function paymentData()
    {
        return $this->hasMany(PaymentData::class, 'payment_method_id');
    }
}
