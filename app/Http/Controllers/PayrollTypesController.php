<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Models\BranchOfficePayrollType;
use App\Models\PayrollAccount;
use App\Models\PayrollTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class PayrollTypesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payrollTypes = PayrollTypes::select('id', 'name', 'active')->get();
        return Inertia::render('PayrollTypes/Index', [
            'payrollTypes' => $payrollTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branchOffices = BranchOffice::select('id', 'code')->get();
        $payrollAccounts = PayrollAccount::select('id', 'name', 'code', 'number')->where('active', 1)->get();
        
        $campos = Schema::getColumnListing('payroll_department_items');
                    
        return Inertia::render('PayrollTypes/Create', [
            'branchOffices' => $branchOffices,
            'payrollAccounts' => $payrollAccounts,
            'keys' => $campos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required',
            'static_fields' => 'required',
            'debit_fields' => 'required',
            'credit_fields' => 'required',
            'requires_date' => 'required',
            'apply_departments' => 'required',
            'branch_offices' => 'required',
        ]);

        //dd($validated);

        $payroll = PayrollTypes::create($validated);
        $lastInserID = $payroll->id;

        foreach ($validated['branch_offices'] as $branchOffice) {
            BranchOfficePayrollType::create([
                'payroll_type_id' => $lastInserID,
                'branch_office_id' => $branchOffice,
            ]);
        }

        return redirect()->route('/payroll-types');
    }

    /**
     * Display the specified resource.
     */
    public function show(PayrollTypes $payrollTypes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayrollTypes $payrollType)
    {
        $branchOffices = BranchOffice::select('id', 'code')->get();
        $payrollAccounts = PayrollAccount::select('id', 'name', 'code')->where('active', 1)->get();
        $keys = Schema::getColumnListing('payroll_department_items');
        $branchOfficesSelected = PayrollTypes::getPayrollBranchOffices($payrollType->id);
                    
        return Inertia::render('PayrollTypes/Edit', [
            'branchOffices' => $branchOffices,
            'payrollAccounts' => $payrollAccounts,
            'keys' => $keys,
            'payrollType' => $payrollType,
            'branchOfficesSelected'=> $branchOfficesSelected,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayrollTypes $payrollType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required',
            'static_fields' => 'required',
            'debit_fields' => 'required',
            'credit_fields' => 'required',
            'requires_date' => 'required',
            'apply_departments' => 'required',
            'branch_offices' => 'required',
        ]);

        $payrollType->update($validated);

        foreach ($validated['branch_offices'] as $branchOffice) {
            BranchOfficePayrollType::where('payroll_type_id', $payrollType->id)->where('branch_office_id', $branchOffice)->delete();
            BranchOfficePayrollType::create([
                'payroll_type_id' => $payrollType->id,
                'branch_office_id' => $branchOffice,
            ]);
        }

        return redirect()->route('/payroll-types');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PayrollTypes $payrollType)
    {
        $payrollType->delete();
        return redirect()->route('/payroll-types');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->ids;
        PayrollTypes::whereIn('id', $ids)->delete();
        return redirect()->route('/payroll-types');
    }
}
