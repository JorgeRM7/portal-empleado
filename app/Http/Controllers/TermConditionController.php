<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
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
        // Buscamos al empleado (tabla employees)
        $employee = Employee::find($id);

        if (!$employee) {
            return back()->withErrors(['message' => 'Empleado no encontrado.']);
        }

        // 1. Actualizamos términos en la tabla 'employees'
        $employee->update([
            'terms_condition' => 1
        ]);

        // 2. Actualizamos el token en la tabla 'user_employees'
        // Usamos el usuario autenticado para estar seguros
        if ($request->has('device_token')) {
            $request->user()->update([
                'device_token' => $request->device_token
            ]);
        }

        return back()->with('success', 'Todo actualizado correctamente.');
    }
}
