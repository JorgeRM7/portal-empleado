<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
class Event extends Model
{
    use SoftDeletes;

    protected $table = 'events';

    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'description',
        'color',
        'all_day',
        'holiday',
        'employee_id',
        'start_date',
        'end_date',
        'only_branch_office',
        'branch_office_id',
        'birthday'
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'holiday' => 'boolean',
        'only_branch_office' => 'boolean',
        'birthday' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deleted_at' => 'datetime'
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function branchOffice()
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

    public static function index( $data ){
        $where = "deleted_at IS NULL";
        
        if (!empty($data['date_start']) && !empty($data['date_end'])) {
            $date_start = $data['date_start'];
            $date_end   = $data['date_end'];

            $where .= " AND start_date BETWEEN '$date_start' AND '$date_end'";
        }
        if ($data['holiday']) {
            if($data['holiday'] == true){
                $holiday = 1;
            }else{
                $holiday = 0;
            }
            
            $where .= " AND holiday = $holiday";
        }

        if ( !empty($data['selectedBranchOfficeId']) ) {
            $branch_office_id = $data['selectedBranchOfficeId'];
            $where .= " AND branch_office_id IN($branch_office_id)";
        }

        $sql = "SELECT * FROM events WHERE $where ORDER BY start_date ASC";
        return DB::select($sql);
    }

}
