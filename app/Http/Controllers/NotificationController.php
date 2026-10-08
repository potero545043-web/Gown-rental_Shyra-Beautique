<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $role = request()->user()->role;

        return view('notifications.index', [
            'notifications' => request()->user()->notifications()->latest()->paginate(20),
            'role' => $role,
            'routePrefix' => $role . '.notifications',
        ]);
    }

    public function markRead(string $notification): RedirectResponse
    {
        $item = request()->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(): RedirectResponse
    {
        request()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
