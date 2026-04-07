<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchOfficeUser extends Model
{
    protected $table = 'branch_office_user';

    protected $fillable = [
        'branch_office_id',
        'user_id',
    ];

    public $timestamps = false;
}
