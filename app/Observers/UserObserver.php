<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\StaffActivityNotification;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->role !== 'customer') {
            return;
        }

        User::where('role', 'owner')->get()->each(
            fn(User $owner) => $owner->notify(new StaffActivityNotification('new_customer', customer: $user))
        );
    }
}
