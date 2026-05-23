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

        // Buscar employee del usuario autenticado
        $employee = Employee::where('id', $authUser->id)->first();

        $user = User::find($employee->user_id);

        // SOLO NOTIFICACIONES RESOLVED
        $unread = $user->unreadNotifications
            ->filter(function ($notification) {
                return ($notification->data['notification_type'] ?? null) === 'RESOLVED';
            })
            ->map(function ($notification) {
                return [
                    'id'                => $notification->id,
                    'type_notification' => $notification->data['notification_type'] ?? null,
                    'data'              => $notification->data,
                    'module'            => $notification->data['notification_module'] ?? null,
                    'status'            => $notification->read_at ? 'read' : 'unread',
                    'read_at'           => $notification->read_at,
                    'relationship_id'   => $notification->data['complain_id'] ?? null,
                    'created_at'        => $notification->created_at->toDateTimeString(),
                ];
            })
            ->values();

        // TODAS LAS NOTIFICACIONES (sin filtro)
        $all = $user->notifications
            ->map(function ($notification) {
                return [
                    'id'                => $notification->id,
                    'type_notification' => $notification->data['notification_type'] ?? null,
                    'data'              => $notification->data,
                    'module'            => $notification->data['notification_module'] ?? null,
                    'status'            => $notification->read_at ? 'read' : 'unread',
                    'read_at'           => $notification->read_at,
                    'relationship_id'   => $notification->data['complain_id'] ?? null,
                    'created_at'        => $notification->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications
                ->filter(fn ($n) => ($n->data['notification_type'] ?? null) === 'RESOLVED')
                ->count(),

            'unread' => $unread,
            'all'    => $all,
        ]);
    }
    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     // Buscar employee del usuario autenticado
    //     $employee = Employee::where('id', $user->id)->first();

    //     $user = User::find($employee->user_id);

    //     // Puedes personalizar qué campos enviar al frontend
    //     $unread = $user->unreadNotifications->map(function ($notification) {
    //         return [
    //             'id'                => $notification->id,
    //             'type_notification' => $notification->type_notification,
    //             'data'              => $notification->data,
    //             'module'            => $notification->module,
    //             'status'            => $notification->status,
    //             'read_at'           => $notification->read_at,
    //             'relationship_id'   => $notification->relationship_id,
    //             'created_at'        => $notification->created_at->toDateTimeString(),
    //         ];
    //     });

    //     $all = $user->notifications->map(function ($notification) {
    //         return [
    //             'id'                => $notification->id,
    //             'type_notification' => $notification->ttype_notificationype,
    //             'data'              => $notification->data,
    //             'module'            => $notification->module,
    //             'status'            => $notification->status,
    //             'read_at'           => $notification->read_at,
    //             'relationship_id'   => $notification->relationship_id,
    //             'created_at'        => $notification->created_at->toDateTimeString(),
    //         ];
    //     });

    //     return response()->json([
    //         'unread_count' => $user->unreadNotifications()->count(),
    //         'unread'       => $unread,
    //         'all'          => $all,
    //     ]);
    // }

    public function markAsRead(Request $request, string $id)
    {
        $authUser = $request->user();

        $employee = Employee::where('id', $authUser->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $notification = Notifications::where('id', $id)
            ->where('notifiable_id', $employee->user_id)
            ->firstOrFail();

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
