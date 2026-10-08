<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\ReservationLifecycleNotification;
use Illuminate\Console\Command;

class SendReservationReminders extends Command
{
    protected $signature = 'app:send-reservation-reminders';

    protected $description = 'Send customer pickup and return reminder notifications';

    public function handle(): int
    {
        $sent = 0;

        Reservation::with(['customer.user', 'items.gown'])
            ->whereDate('pickup_date', today()->addDay())
            ->whereIn('status', ['confirmed', 'ready_for_pickup'])
            ->each(function (Reservation $reservation) use (&$sent): void {
                $sent += (int) $this->notifyOnce($reservation, 'pickup_reminder');
            });

        Reservation::with(['customer.user', 'items.gown'])
            ->whereDate('return_date', today()->addDay())
            ->whereIn('status', ['released', 'overdue'])
            ->each(function (Reservation $reservation) use (&$sent): void {
                $sent += (int) $this->notifyOnce($reservation, 'return_reminder');
            });

        Reservation::with(['customer.user', 'items.gown'])
            ->whereDate('return_date', '<', today())
            ->whereIn('status', ['released', 'overdue'])
            ->each(function (Reservation $reservation) use (&$sent): void {
                $sent += (int) $this->notifyOnce($reservation, 'overdue');
            });

        $this->info("Sent {$sent} reservation reminder notification(s).");

        return self::SUCCESS;
    }

    private function notifyOnce(Reservation $reservation, string $event): bool
    {
        $user = $reservation->customer?->user;

        if ($user?->role !== 'customer') {
            return false;
        }

        $alreadySent = $user->notifications()
            ->where('type', ReservationLifecycleNotification::class)
            ->where('data->reservation_id', $reservation->id)
            ->where('data->event', $event)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $user->notify(new ReservationLifecycleNotification($reservation, $event));

        return true;
    }
}
