<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxData extends Model
{
    use HasFactory;

    protected $table = 'tax_data';

    protected $fillable = [
        'tax_id',
        'postal_code',
        'email',
        'owner_type',
        'tax_system_id',
        'owner_id',
    ];
}
