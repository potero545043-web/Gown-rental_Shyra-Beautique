<?php

namespace App\Observers;

use App\Models\Gown;
use App\Models\User;
use App\Notifications\StaffActivityNotification;

class GownObserver
{
    public function updated(Gown $gown): void
    {
        if (!$gown->wasChanged('status')) {
            return;
        }

        User::where('role', 'owner')->get()->each(
            fn(User $user) => $user->notify(new StaffActivityNotification('gown_status_changed', gown: $gown))
        );
    }
}
