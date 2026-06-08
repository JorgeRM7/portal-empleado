<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use App\Models\Notifications;
use App\Models\Employee;
use App\Models\User;

class NotificationController
{

    public function index(Request $request)
    {
        $authUser = $request->user();

        // OBTENER employee_id DEL USUARIO LOGUEADO
        $employeeId = $authUser->employee_id;

        // NOTIFICACIONES RESUELTAS DEL EMPLEADO
        $resolvedNotifications = DatabaseNotification::query()
            ->where('employee_id', $employeeId)
            ->whereIn('type_notification', [
                'RESOLVED',
                'INCIDENCE_REJECTED',
                'INCIDENCE_APPROVED',
                'NEWS',
            ])
            ->whereNull('notifiable_id')
            ->latest()
            ->get();

        $unread = $resolvedNotifications
            ->whereNull('read_at')
            ->map(function ($notification) {
                return [
                    'id'                => $notification->id,
                    'type_notification' => $notification->type_notification,
                    'data'              => $notification->data,
                    'module'            => $notification->module,
                    'status'            => $notification->read_at ? 'read' : 'unread',
                    'read_at'           => $notification->read_at,
                    'relationship_id'   => $notification->relationship_id,
                    'created_at'        => $notification->created_at->toDateTimeString(),
                ];
            })
            ->values();

        $all = $resolvedNotifications
            ->map(function ($notification) {
                return [
                    'id'                => $notification->id,
                    'type_notification' => $notification->type_notification,
                    'data'              => $notification->data,
                    'module'            => $notification->module,
                    'status'            => $notification->read_at ? 'read' : 'unread',
                    'read_at'           => $notification->read_at,
                    'relationship_id'   => $notification->relationship_id,
                    'created_at'        => $notification->created_at->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $resolvedNotifications
                ->whereNull('read_at')
                ->count(),

            'unread' => $unread,

            'all' => $all,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $authUser = $request->user();
        $employee = Employee::where('id', $authUser->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $notification = Notifications::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $notification->update([
            'read_at' => now()
        ]);

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }
}
