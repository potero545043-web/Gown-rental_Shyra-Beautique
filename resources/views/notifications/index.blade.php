<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">{{ $role === 'owner' ? 'OWNER WORKSPACE' : 'EMPLOYEE WORKSPACE' }}</span>
                    <h1>{{ ucfirst($role) }} <em>notifications.</em></h1>
                    <p>{{ $role === 'owner' ? 'Important reservation, inventory, customer, and employee activity.' : 'Reservation and rental tasks that need your attention.' }}</p>
                </div>
                @if(auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route($routePrefix . '.read-all') }}">
                        @csrf
                        <button class="sb-outline-btn" type="submit">Mark all as read</button>
                    </form>
                @endif
            </div>

            @if(session('success'))
                <div class="sb-success" role="status">{{ session('success') }}</div>
            @endif

            <section class="sb-notifications-list" aria-label="{{ ucfirst($role) }} notifications">
                @forelse($notifications as $notification)
                    @php($data = $notification->data)
                    <article class="sb-notification-card {{ $notification->read_at ? '' : 'is-unread' }}">
                        <div class="sb-notification-icon" aria-hidden="true">
                            @switch($data['event'] ?? '')
                                @case('new_reservation') 🆕 @break
                                @case('pickup_reminder') 📅 @break
                                @case('return_reminder') 🔔 @break
                                @case('overdue') ⚠ @break
                                @case('reservation_cancelled') ❌ @break
                                @case('gown_returned') 📦 @break
                                @case('gown_status_changed') 👗 @break
                                @case('new_customer') 👤 @break
                                @case('employee_activity') 👩‍💼 @break
                                @default 🔄
                            @endswitch
                        </div>
                        <div class="sb-notification-content">
                            <div class="sb-notification-heading">
                                <h2>{{ $data['title'] ?? 'System update' }}</h2>
                                @unless($notification->read_at)
                                    <span>New</span>
                                @endunless
                            </div>
                            <p>{{ $data['message'] ?? 'There is a new activity that may need your attention.' }}</p>
                            <small>{{ $notification->created_at->format('M j, Y · g:i A') }}</small>
                        </div>
                        <div class="sb-notification-actions">
                            <a class="sb-text-link" href="{{ $data['url'] ?? route($role . '.dashboard') }}">Review</a>
                            @unless($notification->read_at)
                                <form method="POST" action="{{ route($routePrefix . '.read', ['notification' => $notification->id]) }}">
                                    @csrf
                                    <button class="sb-notification-read" type="submit">Mark read</button>
                                </form>
                            @endunless
                        </div>
                    </article>
                @empty
                    <div class="sb-panel sb-empty">
                        <span class="sb-kicker">ALL CAUGHT UP</span>
                        <h2>No notifications yet</h2>
                        <p>New reservations, reminders, returns, and other important activity will appear here.</p>
                    </div>
                @endforelse
            </section>

            @if($notifications->hasPages())
                <div class="sb-pagination">{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
