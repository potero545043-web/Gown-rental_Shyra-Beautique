<?php

namespace App\Observers;

use App\Models\ReservationItem;
use App\Models\User;
use App\Notifications\StaffActivityNotification;

class ReservationItemObserver
{
    public function created(ReservationItem $item): void
    {
        if ($item->reservation->items()->whereKeyNot($item->id)->exists()) {
            return;
        }

        $reservation = $item->reservation()->with(['customer', 'items.gown'])->firstOrFail();
        User::whereIn('role', ['owner', 'employee'])->get()->each(
            fn(User $user) => $user->notify(new StaffActivityNotification('new_reservation', $reservation))
        );
    }
}
