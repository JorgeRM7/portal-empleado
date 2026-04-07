<?php

namespace App\Http\Controllers;

use App\Models\PayrollInvoice;
use Inertia\Inertia;
use Illuminate\Http\Request;

class EmailsController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $data = $this->getData();

        return Inertia::render('Emails/Index', [
            'data' => $data
        ]);

    }


    public function filter_data(Request $request)
    {

        $data = $this->getData();

        return response()->json([
            'rows' => $data
        ]);

    }


    private function getData()
    {

        return PayrollInvoice::selectRaw('
                COUNT(payroll_invoices.id) AS correos,
                branch_offices.code,
                branch_offices.name,
                payroll_invoices.week,
                payroll_invoices.year,
                branch_offices.id
            ')
            ->join('branch_offices', 'branch_offices.id', '=', 'payroll_invoices.branch_office_id')
            ->where('payroll_invoices.send_correo', 1)
            ->whereNull('payroll_invoices.estatus_correo')
            ->whereNull('payroll_invoices.deleted_at')
            ->groupBy(
                'branch_offices.code',
                'branch_offices.name',
                'branch_offices.id',
                'payroll_invoices.week',
                'payroll_invoices.year'
            )
            ->orderBy('branch_offices.code')
            ->get();

    }

}
