<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     */
    public function update($user, array $input): void
    {
        $key = 'password-change:' . $user->id . '|' . request()->ip();

        // 🔒 Si ya está bloqueado
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'current_password' => "Demasiados intentos. Intenta en {$seconds} segundos o contacta a RH.",
            ]);
        }

        // Validaciones básicas (SIN current_password:web)
        Validator::make($input, [
            'current_password' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.required' => __('La contraseña actual es obligatoria.'),
            'password.required' => __('La contraseña es obligatoria.'),
            'password.min' => __('La contraseña debe tener al menos 8 caracteres.'),
            'password.confirmed' => __('La contraseña no coincide con la confirmación.'),
        ])->validateWithBag('updatePassword');

        // ❌ Password actual incorrecto
        if (!Hash::check($input['current_password'], $user->password)) {

            RateLimiter::hit($key, 60); // ⏱ 1 minuto

            throw ValidationException::withMessages([
                'current_password' => 'La contraseña actual es incorrecta.',
            ]);
        }

        // ✅ Correcto → limpiar intentos
        RateLimiter::clear($key);

        // guardar nueva contraseña
        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
        // Validator::make($input, [
        //     'current_password' => ['required', 'string', 'current_password:web'],
        //     'password' => $this->passwordRules(),
        // ], [
        //     'current_password.current_password' => __('La contraseña proporcionada no coincide con su contraseña actual.'),
        //     'password.required' => __('La contraseña es obligatoria.'),
        //     'password.min' => __('La contraseña debe tener al menos 8 caracteres.'),
        //     'password.confirmed' => __('La contraseña no coincide con la confirmación.'),
        // ])->validateWithBag('updatePassword');

        // $user->forceFill([
        //     'password' => Hash::make($input['password']),
        // ])->save();
    }
}
