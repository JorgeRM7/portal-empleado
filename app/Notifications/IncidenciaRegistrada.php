<?php

namespace App\Notifications;

use App\Models\Employee;
use App\Models\Incidence;
use App\Notifications\Channels\CustomDatabaseChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidenciaRegistrada extends Notification
{
    use Queueable;
    

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string|int $idIncidence,
        public string|int $employeeId,
        public Employee $employee,
        public ?object $incidenceRecord = null
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [CustomDatabaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $incidenceName = $this->incidenceName();
        $startDate = $this->formatDate($this->incidenceRecord?->validity_from);
        $endDate = $this->formatDate($this->incidenceRecord?->validity_to);

        return [
            'titulo'        => "Incidencia Registrada",
            'descripcion' => "El empleado ({$this->employeeId}) - ({$this->employee->full_name}) registró la incidencia ({$incidenceName}) con fecha de inicio ({$startDate}) y fecha de fin ({$endDate})",
            'notifiable_type' => 'App\Models\User',
            'employee_id'    => $this->employee->id,
            'branch_office_id' => $this->employee->branch_office_id,
            'employee_full_name' => $this->employee->full_name,
            'notification_type' => 'INSERT - Mi Portal RH',
            'notification_module' => 'Incidencias por Empleado',
            'notification_date' => Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s'),
            'relationship_id' => $this->idIncidence
        ];
    }

    private function incidenceName(): string
    {
        if (! $this->incidenceRecord) {
            return 'Incidencia';
        }

        return Incidence::find($this->incidenceRecord->incidence_id)?->name ?? 'Incidencia';
    }

    private function formatDate($date): string
    {
        if (empty($date)) {
            return 'sin fecha';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    
}
