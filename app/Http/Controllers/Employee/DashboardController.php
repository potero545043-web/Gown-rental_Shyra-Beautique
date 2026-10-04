<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\{Gown, Payment, Reservation};

class DashboardController extends Controller
{
    public function index()
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        return view('employee.dashboard', [
            // 4 cards (same as owner reports, this year)
            'reservationCount' => Reservation::whereBetween('created_at', [$start, $end])->count(),
            'rentalCount' => Reservation::whereIn('status', ['released', 'overdue'])->count(),
            'paymentTotal' => Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount'),
            'availableCount' => Gown::where('status', 'available')->count(),
            'gownCount' => Gown::count(),
            'yearLabel' => now()->year,

            // tables
            'reservations' => Reservation::with(['customer', 'items.gown'])->latest()->take(6)->get(),
            'returns' => Reservation::with('customer')
                ->whereIn('status', ['released', 'overdue'])
                ->orderBy('return_date')
                ->take(4)
                ->get(),
        ]);
    }
}