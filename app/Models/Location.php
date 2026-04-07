<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'name',
        'postal_code',
        'code',
        'city_id',
        'state_id'
    ];

    public static function index(){

        $query = " SELECT 
                l.id, 
                l.name as location_name, 
                s.name as state_name, 
                c.name as city_name, 
                l.city_id, 
                l.state_id 
            FROM locations l 
            LEFT JOIN states s ON 
                l.state_id = s.id 
            LEFT JOIN cities c ON 
                l.city_id = c.id
        ";

        // return response()->json($query);
        return DB::select($query);
    }
    
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'location_id');
    }
}
