<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

        $employee->update(['terms_condition' => 1]);

        if ($request->has('device_token') && $request->device_token != null) {

            DB::table('user_device_tokens')->updateOrInsert(
                [
                    'id_user' => $request->user()->id,
                    'device_token' => $request->device_token
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        return back()->with('success', 'Todo actualizado correctamente.');
    }
}
