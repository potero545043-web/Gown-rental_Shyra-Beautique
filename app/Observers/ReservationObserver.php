<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Notifications\ReservationLifecycleNotification;

class ReservationObserver
{
    public function updated(Reservation $reservation): void
    {
        $event = null;

        if ($reservation->wasChanged('status')) {
            $event = match ($reservation->status) {
                'confirmed' => 'confirmed',
                'ready_for_pickup' => 'ready_for_pickup',
                'cancelled' => 'cancelled',
                default => null,
            };
        } elseif ($reservation->wasChanged(['pickup_date', 'return_date'])) {
            $event = 'updated';
        }

        if ($event !== null) {
            $this->notifyCustomer($reservation, $event);
        }
    }

    private function notifyCustomer(Reservation $reservation, string $event): void
    {
        $user = $reservation->customer()->with('user')->first()?->user;

        if ($user?->role === 'customer') {
            $user->notify(new ReservationLifecycleNotification(
                $reservation->loadMissing('items.gown'),
                $event,
            ));
        }
    }
}
