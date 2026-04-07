<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxSystem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_systems';

    protected $fillable = [
        'name',
        'code',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function taxData()
    {
        return $this->hasMany(TaxData::class, 'tax_system_id');
    }
}
