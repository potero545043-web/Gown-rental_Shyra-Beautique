<?php

namespace App\Notifications;

use App\Models\Gown;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $event,
        public readonly ?Reservation $reservation = null,
        public readonly ?Gown $gown = null,
        public readonly ?User $customer = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $reservation = $this->reservation?->loadMissing(['customer', 'items.gown']);
        $gownName = $reservation?->items->first()?->gown?->name ?? $this->gown?->name;
        $reservationCode = $reservation?->reservation_code;
        $date = $reservation?->pickup_date?->format('F j');
        $details = $reservationCode ? "Reservation #{$reservationCode}" : 'A reservation';
        $gownDetails = $gownName ? " for the {$gownName}" : '';

        [$title, $message] = match ($this->event) {
            'new_reservation' => [
                'New reservation',
                ($reservation?->customer?->full_name ?? 'A customer') . " submitted {$details}{$gownDetails} for {$date}.",
            ],
            'pickup_reminder' => [
                'Upcoming pickup',
                "{$details}{$gownDetails} is scheduled for pickup on {$date}; prepare the gown.",
            ],
            'return_reminder' => [
                'Upcoming return',
                "{$details}{$gownDetails} is expected back on " . ($reservation?->return_date?->format('F j') ?? 'the scheduled return date') . '.',
            ],
            'overdue' => [
                'Overdue return',
                "{$details}{$gownDetails} was due for return on " . ($reservation?->return_date?->format('F j') ?? 'the scheduled return date') . '.',
            ],
            'reservation_updated' => [
                'Reservation updated',
                "The details for {$details}{$gownDetails} have changed. Review the reservation.",
            ],
            'reservation_cancelled' => [
                'Reservation cancelled',
                ($reservation?->customer?->full_name ?? 'A customer') . " cancelled {$details}{$gownDetails}.",
            ],
            'gown_returned' => [
                'Gown returned',
                "{$details}{$gownDetails} has been returned and needs inspection.",
            ],
            'employee_activity' => [
                'Employee activity needs attention',
                "{$details}{$gownDetails} was checked in by " . ($reservation?->gownReturn?->processedBy?->name ?? 'an employee') . '. Review the return inspection.',
            ],
            'gown_status_changed' => [
                'Gown status changed',
                ($this->gown?->name ?? 'A gown') . ' status changed from ' . ($this->gown?->getOriginal('status') ?? 'unknown') . ' to ' . ($this->gown?->status ?? 'unknown') . '.',
            ],
            'new_customer' => [
                'New customer registration',
                ($this->customer?->name ?? 'A new customer') . ' registered an account.',
            ],
            default => throw new \InvalidArgumentException("Unsupported staff notification event: {$this->event}"),
        };

        return [
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'reservation_id' => $reservation?->id,
            'reservation_code' => $reservationCode,
            'gown_id' => $this->gown?->id,
            'customer_id' => $this->customer?->id ?? $reservation?->customer_id,
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        $role = $notifiable instanceof User ? $notifiable->role : 'employee';

        return match ($this->event) {
            'gown_status_changed' => route('owner.gowns.show', $this->gown),
            'new_customer' => route('owner.customers'),
            'return_reminder', 'overdue', 'gown_returned', 'employee_activity' => route($role . '.rentals'),
            default => route($role . '.reservations'),
        };
    }
}
