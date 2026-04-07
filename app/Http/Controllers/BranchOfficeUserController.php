<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BranchOfficeUserController
{
    public function index()
    {
    }

    public function getBranchOfficesUser()
    {
        $branchOffices = DB::select(
            'SELECT bo.code, bo.id, bo.name
            FROM branch_office_user bou
            INNER JOIN branch_offices bo ON bo.id = bou.branch_office_id
            WHERE bou.user_id = ?',
            [Auth::user()->id]  
        );

        return response()->json($branchOffices);
    }
    
}
