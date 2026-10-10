<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">YOUR ACCOUNT</span>
                    <h1>Customer <em>notifications.</em></h1>
                    <p>Updates and reminders about your gown reservations.</p>
                </div>
                @if(auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                        @csrf
                        <button class="sb-outline-btn" type="submit">Mark all as read</button>
                    </form>
                @endif
            </div>

            @if(session('success'))
                <div class="sb-success" role="status">{{ session('success') }}</div>
            @endif

            <section class="sb-notifications-list" aria-label="Customer notifications">
                @forelse($notifications as $notification)
                    @php($data = $notification->data)
                    <article class="sb-notification-card {{ $notification->read_at ? '' : 'is-unread' }}">
                        <div class="sb-notification-icon" aria-hidden="true">
                            @switch($data['event'] ?? '')
                                @case('confirmed') ✓ @break
                                @case('ready_for_pickup') 📦 @break
                                @case('pickup_reminder') ▦ @break
                                @case('return_reminder') 🔔 @break
                                @case('overdue') ⚠ @break
                                @case('cancelled') × @break
                                @default ↻
                            @endswitch
                        </div>
                        <div class="sb-notification-content">
                            <div class="sb-notification-heading">
                                <h2>{{ $data['title'] ?? 'Reservation update' }}</h2>
                                @unless($notification->read_at)
                                    <span>New</span>
                                @endunless
                            </div>
                            <p>{{ $data['message'] ?? 'There is an update to your reservation.' }}</p>
                            <small>{{ $notification->created_at->format('M j, Y · g:i A') }}</small>
                        </div>
                        <div class="sb-notification-actions">
                            <a class="sb-text-link"
                                href="{{ $data['url'] ?? route('customer.reservations') }}">View reservation</a>
                            @unless($notification->read_at)
                                <form method="POST"
                                    action="{{ route('customer.notifications.read', ['notification' => $notification->id]) }}">
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
                        <p>Reservation confirmations, pickup and return reminders, and other updates will appear here.</p>
                    </div>
                @endforelse
            </section>

            @include('components.table-pagination', ['paginator' => $notifications, 'itemLabel' => 'notifications'])
        </div>
    </div>
</x-app-layout>
