<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class TermConditionController
{
    public function index()
    {
        $data = [];
        return Inertia::render('TermConditions/Index', [
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return back()->withErrors(['message' => 'Empleado no encontrado.']);
        }


        $employee->update([
            'terms_condition' => 1
        ]);

            return back()->with('success', 'Términos aceptados correctamente.');

        return back()->withErrors([
            'message' => "No autorizado."
        ]);
    }
}
