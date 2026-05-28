<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController
{
    /**
     * Actualizar la contraseña del usuario
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
        ], [
            'current_password.required' => 'La contraseña actual es requerida.',
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
            'password.required' => 'La nueva contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed_case' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir números.',
        ]);

        // Actualizar contraseña y desactivar el flag de cambio obligatorio
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'password_change' => 0,
        ]);

        return back()->with('status', 'password-updated');
    }
}