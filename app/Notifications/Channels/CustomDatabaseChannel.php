<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class CustomDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        // Aquí extraemos los datos que definiste en la notificación
        $data = $notification->toDatabase($notifiable);

        // Insertamos manualmente en la tabla con tus columnas personalizadas
        return $notifiable->routeNotificationFor('database')->create([
            'id'          => $notification->id,
            'type'        => get_class($notification),
            'data'        => $data,
            'read_at'     => null,
            //COLUMNAS PERSONALIZADAS:
            'employee_id' => $data['employee_id'] ?? null,
            'branch_office_id' => $data['branch_office_id'] ?? null,
            'employee_full_name' => $data['employee_full_name'] ?? null,
            'type_notification' => $data['notification_type'] ?? null,
            'module' => $data['notification_module'] ?? null,
            'notification_date' => $data['notification_date'] ?? null,
        ]);
    }
}