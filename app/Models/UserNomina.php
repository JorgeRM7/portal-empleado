<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserNomina extends Model
{
    use Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'id';
}
