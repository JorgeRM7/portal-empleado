<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\CustomDatabaseChannel;
use Carbon\Carbon;
use App\Models\Employee;

class IncidenceStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $incidenceName,
        public ?Employee $employee = null,
        public ?int $incidenceRecordId = null,
        public ?object $incidenceRecord = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return [CustomDatabaseChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'APPROVED';
        $dateText = $this->dateDescription();

        return [
            'titulo' => $isApproved
                ? 'Incidencia Aprobada'
                : 'Incidencia Rechazada',

            'descripcion' => $isApproved
                ? "Tu incidencia '{$this->incidenceName}' fue aprobada{$dateText}"
                : "Tu incidencia '{$this->incidenceName}' fue rechazada{$dateText}",

            'notifiable_type' => 'App\Models\User',

            'employee_id' => $this->employee?->id,

            'branch_office_id' => $this->employee?->branch_office_id,

            'employee_full_name' =>
                $this->employee?->full_name,

            'notification_type' => $isApproved
                ? 'INCIDENCE_APPROVED'
                : 'INCIDENCE_REJECTED',

            'notification_module' => 'Incidences',

            'notification_date' => Carbon::now('America/Mexico_City')
                ->format('Y-m-d H:i:s'),

            'status' => $this->status,

            'relationship_id' => $this->incidenceRecordId,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    private function dateDescription(): string
    {
        if (! $this->incidenceRecord) {
            return '';
        }

        if (in_array((int) $this->incidenceRecord->incidence_id, [19, 20], true)) {
            return sprintf(
                ' con fecha de descanso %s y fecha de reposicion %s',
                $this->formatDate($this->incidenceRecord->rest_date ?? null),
                $this->formatDate($this->incidenceRecord->validity_from ?? null)
            );
        }

        return sprintf(
            ' con fecha de inicio %s y fecha de fin %s',
            $this->formatDate($this->incidenceRecord->validity_from ?? null),
            $this->formatDate($this->incidenceRecord->validity_to ?? null)
        );
    }

    private function formatDate($date): string
    {
        if (empty($date)) {
            return 'sin fecha';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
