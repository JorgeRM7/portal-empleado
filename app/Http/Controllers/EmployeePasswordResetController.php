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


   
    public function verifyCode(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'integer'],
            'code' => ['required', 'digits:6'],
        ]);

        $reset = UserEmployeePasswordResetCode::where('employee_id',$request->employee_id)
            ->latest('id')
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'No existe una solicitud de recuperación.',
            ], 404);
        }

        $now = Carbon::now('America/Mexico_City');

        $expiresAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $reset->getRawOriginal('expires_at'),
            'America/Mexico_City'
        );

        if ($now->greaterThanOrEqualTo($expiresAt)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicite uno nuevo.',
            ], 422);
        }
        if (!Hash::check( $request->code, $reset->code) ) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado no es correcto.',
            ], 422);
        }

        $reset->update([
            'verified_at' => Carbon::now('America/Mexico_City'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Código verificado correctamente.',
            'reset_employee_id' => $reset->employee_id,
            'reset_step' => 'password',
        ]);
    }



    public function resetPassword(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'integer'],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $reset = UserEmployeePasswordResetCode::where('employee_id', $request->employee_id)
            ->whereNotNull('verified_at')
            ->latest('id')
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debe verificar el código enviado a su correo.',
            ], 422);
        }

        $now = Carbon::now('America/Mexico_City');

        $expiresAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $reset->getRawOriginal('expires_at'),
            'America/Mexico_City'
        );

        if ($now->greaterThanOrEqualTo($expiresAt)) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ha expirado. Inicie nuevamente el proceso.',
            ], 422);
        }

        $user = UserEmployee::where('employee_id', $request->employee_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el usuario.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        UserEmployeePasswordResetCode::where('employee_id', $reset->employee_id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }



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