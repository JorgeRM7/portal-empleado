<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeSearchController
{
    /**
     * Display a listing of the resource.
     */
    public function index($id = null)
    {
        $user = Auth::user();

        $branchOfficeIds = $user->branchOffices()
            ->where('active', true)
            ->pluck('branch_offices.id');

        $employees = Employee::select('id', 'full_name')
            ->whereIn('branch_office_id', $branchOfficeIds)
            ->orderBy('id', 'ASC')
            ->get();

        return Inertia::render('EmployeeSearch/Index', [
            'employees' => $employees,
            'employee_id' => $id
        ]);
    }

    public function search(Request $request){
        $data = EmployeeSearch::searchEmployee($request->employee_id);

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeSearch $employeeSearch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeSearch $employeeSearch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeSearch $employeeSearch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeSearch $employeeSearch)
    {
        //
    }
}
