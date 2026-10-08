<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Observers\ReservationObserver;
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
