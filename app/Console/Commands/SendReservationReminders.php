<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\ReservationLifecycleNotification;
use App\Notifications\StaffActivityNotification;
use App\Models\User;
use Illuminate\Console\Command;

class SendReservationReminders extends Command
{
    protected $signature = 'app:send-reservation-reminders';

    protected $description = 'Send customer, employee, and owner pickup and return reminder notifications';

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
        $sent = 0;
        $customer = $reservation->customer?->user;

        if ($customer?->role === 'customer') {
            $sent += (int) $this->notifyUserOnce($customer, $reservation, $event, ReservationLifecycleNotification::class);
        }

        $staffEvent = $event === 'overdue' ? 'overdue' : $event;
        User::whereIn('role', ['owner', 'employee'])->get()->each(function (User $user) use ($reservation, $staffEvent, &$sent): void {
            $sent += (int) $this->notifyUserOnce($user, $reservation, $staffEvent, StaffActivityNotification::class);
        });

        return $sent;
    }

    private function notifyUserOnce(User $user, Reservation $reservation, string $event, string $notificationClass): bool
    {
        $alreadySent = $user->notifications()
            ->where('type', $notificationClass)
            ->where('data->reservation_id', $reservation->id)
            ->where('data->event', $event)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        if ($notificationClass === ReservationLifecycleNotification::class) {
            $user->notify(new ReservationLifecycleNotification($reservation, $event));
        } else {
            $user->notify(new StaffActivityNotification($event, $reservation));
        }

        return true;
    }
}
