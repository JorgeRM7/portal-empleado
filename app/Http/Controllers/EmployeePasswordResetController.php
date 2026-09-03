<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\UserEmployee;
use App\Models\User;
use App\Models\UserEmployeePasswordResetCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class EmployeePasswordResetController
{

    public function index()
    {
        return Inertia::render('Auth/ForgotPassword');
    }



    public function sendCode(Request $request)
    {
        $request->validate([
            'employee_code' => ['required', 'string'],
        ]);

        $UserEmployee = UserEmployee::where('employee_id', $request->employee_code)
            ->whereNull('deleted_at')
            ->first();

        $Employee = Employee::where('id', $request->employee_code)
            ->whereNull('deleted_at')
            ->first();

        if (!$Employee) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un empleado con este número de nómina.',
            ], 404);
        }

        if ($Employee->status === 'termination') {
            return response()->json([
                'success' => false,
                'message' => 'El empleado se encuentra dado de baja.',
            ], 422);
        }

        if (!$UserEmployee) {
            return response()->json([
                'success' => false,
                'message' => 'El empleado no cuenta con acceso al portal.',
            ], 404);
        }

        $email = $UserEmployee->email;

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'El empleado no cuenta con un correo registrado.',
            ], 422);
        }

        UserEmployeePasswordResetCode::where(
            'employee_id',
            $Employee->id
        )->delete();

        $code = (string) random_int(100000, 999999);

        UserEmployeePasswordResetCode::create([
            'employee_id' => $Employee->id,

            // Si user_id representa el registro de user_employees
            'user_id' => $UserEmployee->id,

            'email' => $email,
            'code' => Hash::make($code),
            'expires_at' => Carbon::now('America/Mexico_City')->addMinutes(15),
            'verified_at' => null,
        ]);

        Mail::send(
            'emails.password-reset-code',
            [
                'employee' => $Employee,
                'code' => $code,
            ],
            function ($message) use ($email) {
                $message
                    ->to($email)
                    ->subject('Código para recuperar contraseña');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Código enviado correctamente.',
            'reset_employee_id' => $Employee->id,
            'reset_email' => $this->maskEmail($email),
            'expires_in' => 900,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Verificar código
    |--------------------------------------------------------------------------
    */

    public function verifyCode(Request $request)
    {
        $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],

            'code' => [
                'required',
                'digits:6',
            ],
        ]);


        $reset = UserEmployeePasswordResetCode::where(
                'employee_id',
                $request->employee_id
            )
            ->latest('id')
            ->first();


        if (!$reset) {

            return back()->withErrors([
                'code' =>
                    'No existe una solicitud de recuperación.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validar vencimiento
        |--------------------------------------------------------------------------
        */

        if (
            Carbon::now(
                'America/Mexico_City'
            )->greaterThan(
                $reset->expires_at
            )
        ) {

            return back()->withErrors([
                'code' =>
                    'El código ha expirado. Solicite uno nuevo.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validar código
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $request->code,
                $reset->code
            )
        ) {

            return back()->withErrors([
                'code' =>
                    'El código ingresado no es correcto.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Marcar código como verificado
        |--------------------------------------------------------------------------
        */

        $reset->update([
            'verified_at' =>
                Carbon::now(
                    'America/Mexico_City'
                ),
        ]);


        return back()->with([
            'reset_step' =>
                'password',

            'reset_employee_id' =>
                $reset->employee_id,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Cambiar contraseña
    |--------------------------------------------------------------------------
    */

    public function resetPassword(Request $request)
    {
        $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Buscar código previamente verificado
        |--------------------------------------------------------------------------
        */

        $reset = UserEmployeePasswordResetCode::where(
                'employee_id',
                $request->employee_id
            )
            ->whereNotNull(
                'verified_at'
            )
            ->latest('id')
            ->first();


        if (!$reset) {

            return back()->withErrors([
                'password' =>
                    'Primero debe verificar el código enviado a su correo.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Revisar que tampoco haya expirado
        |--------------------------------------------------------------------------
        */

        if (
            Carbon::now(
                'America/Mexico_City'
            )->greaterThan(
                $reset->expires_at
            )
        ) {

            return back()->withErrors([
                'password' =>
                    'La solicitud ha expirado. Inicie nuevamente el proceso.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Obtener usuario
        |--------------------------------------------------------------------------
        */

        $user = User::find(
            $reset->user_id
        );

        if (!$user) {

            return back()->withErrors([
                'password' =>
                    'No se encontró el usuario.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Actualizar contraseña
        |--------------------------------------------------------------------------
        */

        $user->update([
            'password' =>
                Hash::make(
                    $request->password
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Eliminar todos los códigos
        |--------------------------------------------------------------------------
        */

        UserEmployeePasswordResetCode::where(
            'employee_id',
            $reset->employee_id
        )->delete();


        /*
        |--------------------------------------------------------------------------
        | Regresar al login
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Contraseña actualizada correctamente. Ya puede iniciar sesión.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Ocultar parte del correo
    |--------------------------------------------------------------------------
    */

    private function maskEmail(string $email): string
    {
        if (
            !str_contains(
                $email,
                '@'
            )
        ) {
            return $email;
        }

        [$username, $domain] =
            explode(
                '@',
                $email,
                2
            );

        $visible =
            substr(
                $username,
                0,
                2
            );

        $hidden =
            str_repeat(
                '*',
                max(
                    strlen($username) - 2,
                    4
                )
            );

        return
            $visible .
            $hidden .
            '@' .
            $domain;
    }
}