<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-logout-url" content="{{ route('logout') }}">
    <meta name="login-password-public-key"
        content="{{ app(\App\Services\LoginPasswordEncryption::class)->publicKey() }}">

    <title>{{ config('app.name', 'Shyra Beautique') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:400,500,600,700&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/gold-palette.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="sb-app-shell" x-data="{ sidebarOpen: false }">
        @include('layouts.navigation')
        <div class="sb-main-column">
            @php
                $topRole = auth()->user()->role;
                $topDashboard = [
                    'owner' => 'owner.dashboard',
                    'employee' => 'employee.dashboard',
                    'customer' => 'customer.dashboard',
                ][$topRole] ?? null;
                $topCartRole = in_array($topRole, ['customer', 'employee', 'owner'], true) ? $topRole : null;
                $topCartCount = $topCartRole
                    ? \App\Services\ReservationCart::forRole($topCartRole)->count()
                    : 0;

                // Bell dropdown: latest notifications plus the unread count. Read a
                // few more than we show so "99+" style counts stay accurate.
                $topUnread = auth()->user()->unreadNotifications()->count();
                $topNotifications = auth()->user()->notifications()->latest()->take(6)->get();
            @endphp
            <header class="sb-worktop">
                <button type="button" class="sb-mobile-toggle" @click="sidebarOpen = true"
                    aria-label="Open navigation menu">
                    <span></span><span></span><span></span>
                </button>

                <div class="sb-worktop-context">
                    <span class="sb-kicker">SHYRA BEAUTIQUE</span>
                    <b>{{ $topDashboard ? ucfirst($topRole) . ' workspace' : 'Gown rental studio' }}</b>
                </div>

                <div class="sb-worktop-right">
                    @if($topCartRole)
                        <a class="sb-worktop-cart" href="{{ route($topCartRole . '.cart') }}"
                            aria-label="Open my cart">
                            <svg><use href="#sb-i-cart" /></svg>
                            @if($topCartCount > 0)
                                <span class="sb-worktop-cart-count">{{ $topCartCount > 99 ? '99+' : $topCartCount }}</span>
                            @endif
                        </a>
                    @endif

                    {{-- NOTIFICATIONS BELL --}}
                    <div class="sb-bell" x-data="{ open: false }" @click.away="open = false">
                        <button type="button" class="sb-bell-btn" @click="open = !open"
                            :aria-expanded="open.toString()" aria-label="Notifications">
                            <svg><use href="#sb-i-bell" /></svg>
                            @if($topUnread > 0)
                                <span class="sb-bell-count">{{ $topUnread > 99 ? '99+' : $topUnread }}</span>
                            @endif
                        </button>

                        <div class="sb-bell-panel" x-show="open" x-transition.origin.top.right
                            x-cloak role="menu">
                            <div class="sb-bell-head">
                                <b>Notifications</b>
                                @if($topUnread > 0)
                                    <form method="POST" action="{{ route($topRole . '.notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="sb-bell-markall">Mark all read</button>
                                    </form>
                                @endif
                            </div>

                            <div class="sb-bell-list">
                                @forelse($topNotifications as $notification)
                                    @php($data = $notification->data)
                                    <a class="sb-bell-item {{ $notification->read_at ? '' : 'is-unread' }}"
                                        href="{{ route($topRole . '.notifications') }}">
                                        <span class="sb-bell-item-dot" aria-hidden="true"></span>
                                        <span class="sb-bell-item-copy">
                                            <b>{{ $data['title'] ?? ucfirst(str_replace('_', ' ', $data['event'] ?? 'Update')) }}</b>
                                            <small>{{ \Illuminate\Support\Str::limit($data['message'] ?? '', 70) }}</small>
                                            <em>{{ $notification->created_at?->diffForHumans() }}</em>
                                        </span>
                                    </a>
                                @empty
                                    <p class="sb-bell-empty">No notifications yet.</p>
                                @endforelse
                            </div>

                            <a class="sb-bell-footer" href="{{ route($topRole . '.notifications') }}">View all notifications</a>
                        </div>
                    </div>

                    <span class="sb-worktop-name">{{ auth()->user()->name }}</span>
                    <span class="sb-avatar sb-worktop-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </header>
            @isset($header)
                <div class="sb-page-heading">{{ $header }}</div>
            @endisset
            <main class="sb-main-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('layouts.logout-confirm')
</body>

</html>