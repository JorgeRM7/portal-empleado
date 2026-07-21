<?php

namespace App\Notifications;

use App\Models\BranchOffice;
use App\Models\Employee;
use App\Models\EmployeeComplains;
use App\Notifications\Channels\CustomDatabaseChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignment extends Notification
{
    use Queueable;


    /**
     * Create a new notification instance.
     */
    public function __construct(public string $modulo, public string|int $registroId, public Employee $employee)
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
        $notificationDate = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
        $details = $this->ticketDetails();
        return [
            'titulo'    => "Nuevo Ticket Asignado",
            'descripcion'   => "[{$details['branch_office']}] Se te ha asignado un ticket del trabajador ({$details['employee']}) levantado el ({$details['ticket_date']}). \"{$notificationDate}\"",
            'modulo'    => $this->modulo,
            'registroId'=> $this->registroId,
            'employee_id'    => $this->employee->id,
            'notifiable_type' => 'App\Models\User',
            'branch_office_id' => $this->employee->branch_office_id,
            'employee_full_name' => $this->employee->full_name,
            'notification_type' => 'INSERT - Mi Portal RH',
            'notification_module' => 'Tickets',
            'notification_date' => Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s'),
            'relationship_id' => $this->registroId

        ];

    }

    private function ticketDetails(): array
    {
        $complain = EmployeeComplains::find($this->registroId);
        $employee = $complain ? Employee::find($complain->employee_id) : $this->employee->id;
        $branchOffice = $complain ? BranchOffice::find($complain->branch_office_id) : null;

        $employeeCode = $employee?->code ?? $employee?->id ?? 'Sin clave';
        $employeeFullName = $employee?->full_name ?? 'Sin nombre';
        $branchOfficeName = trim(implode(' - ', array_filter([
            $branchOffice?->code,
            $branchOffice?->name,
        ]))) ?: 'Sin planta';
        $ticketDate = $complain?->date
            ? Carbon::parse($complain->date)->format('d/m/Y')
            : 'Sin fecha';

        return [
            'branch_office' => $branchOfficeName,
            'branch_office_id' => $complain?->branch_office_id ?? $employee?->branch_office_id,
            'employee_id' => $employee?->id,
            'employee_full_name' => $employeeFullName,
            'employee' => "{$employeeCode} - {$employeeFullName}",
            'ticket_date' => $ticketDate,
        ];
    }


}
