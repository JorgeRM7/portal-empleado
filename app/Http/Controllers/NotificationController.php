<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Puedes personalizar qué campos enviar al frontend
        $unread = $user->unreadNotifications->map(function ($notification) {
            return [
                'id'         => $notification->id,
                'type'       => class_basename($notification->type),
                'data'       => $notification->data,
                'read_at'    => $notification->read_at,
                'created_at' => $notification->created_at->toDateTimeString(),
            ];
        });

        $all = $user->notifications->map(function ($notification) {
            return [
                'id'         => $notification->id,
                'type'       => class_basename($notification->type),
                'data'       => $notification->data,
                'read_at'    => $notification->read_at,
                'created_at' => $notification->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'unread'       => $unread,
            'all'          => $all,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }
}
