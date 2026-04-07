<?php

namespace App\Http\Controllers;

use App\Notifications\RegistroEliminado;
use App\Notifications\RegistroGuardado;
use Illuminate\Http\Request;

class ApiPruebaController
{

    public function index()
    {
        $datos = [
            [
                'id' => 1,
                'nombre' => 'Juan Pérez',
                'correo' => 'juan.perez@example.com',
                'rol' => 'Administrador',
                'telefono' => '1234567890',
                'direccion' => 'Calle Falsa 123',
                'ciudad' => 'Ciudad Ejemplo'
            ],
            [
                'id' => 2,
                'nombre' => 'María López',
                'correo' => 'maria.lopez@example.com',
                'rol' => 'Usuario',
                'telefono' => '0987654321',
                'direccion' => 'Avenida Siempre Viva 742',
                'ciudad' => 'Otra Ciudad'
            ],
            [
                'id' => 3,
                'nombre' => 'Carlos Ruiz',
                'correo' => 'carlos.ruiz@example.com',
                'rol' => 'Supervisor',
                'telefono' => '5551234567',
                'direccion' => 'Boulevard Central 456',
                'ciudad' => 'Ciudad Central'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $u->notify(new RegistroGuardado('Pruebas', 1));

        return response()->json(['ok' => true,
                                'post' => 1], 201);
    }

    public function destroy(Request $request)
    {
        $u = $request->user();
        $u->notify(new RegistroEliminado('Pruebas', 1));

        return response()->json(['ok' => true,
                                'post' => 1], 201);
    }
}
