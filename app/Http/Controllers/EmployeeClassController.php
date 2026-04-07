<?php

namespace App\Http\Controllers;

use App\Models\EmployeeClass;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeClassController
{
    public function index(Request $request)
    {
        $data = EmployeeClass::get();
        return Inertia::render('EmployeeClass/Index', [
            "data" => $data,
        ]);
    }

    public function create()
    {
        return Inertia::render('EmployeeClass/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:employee_classes,name',
            'code' => 'required|string|max:100|unique:employee_classes,code',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe una clase con ese nombre.',
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Ya existe una clase con ese código.',
        ]);

        EmployeeClass::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()
            ->route('employee-classes.index')
            ->with('success', 'Clase de empleado creada correctamente.');
    }

    public function show(EmployeeClass $employeeClass)
    {
        return Inertia::render('EmployeeClass/Show', [
            'data' => $employeeClass
        ]);
    }

    public function edit(EmployeeClass $employeeClass)
    {
        return Inertia::render('EmployeeClass/Edit', [
            'data' => $employeeClass
        ]);
    }

    public function update(Request $request, EmployeeClass $employeeClass)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:employee_classes,name,' . $employeeClass->id,
            'code' => 'required|string|max:100|unique:employee_classes,code,' . $employeeClass->id,
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe una clase con ese nombre.',
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Ya existe una clase con ese código.',
        ]);

        $employeeClass->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()
            ->route('employee-classes.index')
            ->with('success', 'Clase de empleado actualizada correctamente.');
    }

     public function destroy( $recordID )
    {
        $record= EmployeeClass::find($recordID);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente',
            'registro' => $record
        ]);
    }
}