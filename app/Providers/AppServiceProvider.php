<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Gown;
use App\Models\User;
use App\Observers\GownObserver;
use App\Observers\ReservationObserver;
use App\Observers\ReservationItemObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        ReservationItem::observe(ReservationItemObserver::class);
        Gown::observe(GownObserver::class);
        User::observe(UserObserver::class);

        RedirectIfAuthenticated::redirectUsing(function (): string {
            $role = auth()->user()?->role;

            return match ($role) {
                'owner' => route('owner.dashboard'),
                'employee' => route('employee.dashboard'),
                'customer' => route('customer.dashboard'),
                default => '/',
            };
        });
    }
}
