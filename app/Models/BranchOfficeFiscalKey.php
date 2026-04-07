<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchOfficeFiscalKey extends Model
{
    
    use SoftDeletes;

    protected $table = 'branch_office_fiscal_keys';

    protected $fillable = [
        'certificate_path',
        'key_path',
        'passphrase',
        'branch_office_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
