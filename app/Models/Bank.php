<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory;

    protected $table = 'banks';

    protected $fillable = [
        'name',
        'description',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];


    public function taxData()
    {
        return $this->hasMany(TaxData::class, 'bank_id');
    }
}
