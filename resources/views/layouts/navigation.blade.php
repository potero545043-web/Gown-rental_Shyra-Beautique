@php
    $role = auth()->user()->role;

    $dashboard = [
        'owner' => 'owner.dashboard',
        'employee' => 'employee.dashboard',
        'customer' => 'customer.dashboard',
    ][$role] ?? 'login';

    // Sidebar sections: [label, route name, icon].
    // Every route listed here MUST exist in routes/web.php for that role,
    // otherwise the page crashes with "Route [...] not defined".
    $sections = match ($role) {
        'owner' => [
            'OPERATIONS' => [
                ['Overview', 'owner.dashboard', 'grid'],
                ['Catalog', 'owner.catalog', 'dress'],
                ['Reservations', 'owner.reservations', 'calendar'],
                ['Rentals & returns', 'owner.rentals', 'calendar'],
            ],
            'BUSINESS' => [
                ['Customers', 'owner.customers', 'users'],
                ['Payments', 'owner.payments', 'card'],
            ],
            'MANAGEMENT' => [
                ['Employees', 'owner.employees', 'team'],
                ['Archive', 'owner.archive', 'dress'],
                ['Reports', 'owner.reports', 'chart'],
                ['Settings', 'owner.settings', 'tag'],
            ],
        ],

        // Employee: no Customers, no Maintenance (owner only).
        'employee' => [
            'OPERATIONS' => [
                ['Overview', 'employee.dashboard', 'grid'],
                ['Reservations', 'employee.reservations', 'calendar'],
                ['Rentals & returns', 'employee.rentals', 'calendar'],
            ],
            'BUSINESS' => [
                ['Collection', 'employee.catalog', 'dress'],
                ['Payments', 'employee.payments', 'card'],
            ],
        ],

        default => [
            'MY ACCOUNT' => [
                ['Dashboard', 'customer.dashboard', 'grid'],
                ['Collection', 'customer.catalog', 'dress'],
                ['My Reservations', 'customer.reservations', 'calendar'],
            ],
        ],
    };

    // Active state: exact route, or any child route of a catalog / inventory page.
    $isActive = function ($routeName) {
        $active = request()->routeIs($routeName)
            || (str_ends_with($routeName, '.catalog') && request()->routeIs($routeName . '.*'))
            || ($routeName === 'owner.gowns.index' && request()->routeIs('owner.gowns.*'))
            || ($routeName === 'owner.categories.index' && request()->routeIs('owner.categories.*'))
            || ($routeName === 'owner.accessories.index' && request()->routeIs('owner.accessories.*'))
            || ($routeName === 'owner.damages' && request()->routeIs('owner.damages*'))
            || ($routeName === 'owner.purchases' && request()->routeIs('owner.purchases*'));

        return $active ? 'is-active' : '';
    };

    $inventoryRoutes = ['owner.gowns.*', 'owner.categories.*', 'owner.accessories.*', 'owner.maintenance', 'owner.damages*', 'owner.purchases*'];
    $hasPending = $role !== 'customer' && \App\Models\Reservation::where('status', 'pending')->exists();

    $cartRole = in_array($role, ['customer', 'employee', 'owner'], true) ? $role : null;
    $cartCount = $cartRole
        ? \App\Services\ReservationCart::forRole($cartRole)->count()
        : 0;
@endphp

{{-- ICON LIBRARY --}}
<svg class="sb-icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="sb-i-grid" viewBox="0 0 24 24">
        <rect x="3" y="3" width="7" height="7" rx="1.5" />
        <rect x="14" y="3" width="7" height="7" rx="1.5" />
        <rect x="3" y="14" width="7" height="7" rx="1.5" />
        <rect x="14" y="14" width="7" height="7" rx="1.5" />
    </symbol>
    <symbol id="sb-i-calendar" viewBox="0 0 24 24">
        <rect x="3" y="5" width="18" height="16" rx="2" />
        <path d="M16 3v4M8 3v4M3 10h18M8 14h2m4 0h2m-8 4h2" />
    </symbol>
    <symbol id="sb-i-dress" viewBox="0 0 24 24">
        <path d="M9 3h6l1 4 4 3-3 3-2-1 3 9H6l3-9-2 1-3-3 4-3 1-4zM9 7h6" />
    </symbol>
    <symbol id="sb-i-tag" viewBox="0 0 24 24">
        <path d="M20 13 13 20 3 10V4h6l11 9z" />
        <circle cx="7.5" cy="7.5" r="1" />
    </symbol>
    <symbol id="sb-i-spark" viewBox="0 0 24 24">
        <path
            d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3zM19 16l1 2.5 2.5 1-2.5 1L19 23l-1-2.5-2.5-1 2.5-1L19 16z" />
    </symbol>
    <symbol id="sb-i-users" viewBox="0 0 24 24">
        <path
            d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm7-7.8a4 4 0 0 1 0 7.6M21 21v-2a4 4 0 0 0-3-3.9" />
    </symbol>
    <symbol id="sb-i-team" viewBox="0 0 24 24">
        <circle cx="9" cy="8" r="3.5" />
        <circle cx="17" cy="9" r="2.5" />
        <path d="M2.5 20a6.5 6.5 0 0 1 13 0m1-5a5 5 0 0 1 5 5" />
    </symbol>
    <symbol id="sb-i-card" viewBox="0 0 24 24">
        <rect x="2.5" y="5" width="19" height="14" rx="2" />
        <path d="M3 10h18m-14 5h4" />
    </symbol>
    <symbol id="sb-i-bell" viewBox="0 0 24 24">
        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12a2 2 0 0 0 4 0" />
    </symbol>
    <symbol id="sb-i-chart" viewBox="0 0 24 24">
        <path d="M3 3v18h18M8 16v-4m5 4V6m5 10V9" />
    </symbol>
    <symbol id="sb-i-user" viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="4" />
        <path d="M4 21a8 8 0 0 1 16 0" />
    </symbol>
    <symbol id="sb-i-cart" viewBox="0 0 24 24">
        <path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.2L21 7H6" />
        <circle cx="9.5" cy="20" r="1.4" />
        <circle cx="17.5" cy="20" r="1.4" />
    </symbol>
    <symbol id="sb-i-logout" viewBox="0 0 24 24">
        <path d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
    </symbol>
</svg>

<aside class="sb-sidebar" :class="sidebarOpen ? 'sb-sidebar-open' : ''">

    {{-- BRAND --}}
    <a class="sb-brand sb-sidebar-brand" href="{{ route($dashboard) }}">
        <span class="sb-logo-avatar">
            <img src="{{ asset('images/Logo.png') }}" alt="Shyra Beautique" class="sb-sidebar-logo">
        </span>
        <span class="sb-sidebar-brand-copy">
            <b>Shyra Beautique</b>
            <small>GOWN RENTAL STUDIO</small>
        </span>
    </a>

    {{-- SECTIONS --}}
    @foreach($sections as $sectionLabel => $sectionLinks)
        <div class="sb-sidebar-caption">{{ $sectionLabel }}</div>

        <nav class="sb-side-links" data-nav-section="{{ \Illuminate\Support\Str::slug($sectionLabel) }}">
            @foreach($sectionLinks as [$label, $routeName, $icon])
                <a class="sb-side-link {{ $isActive($routeName) }}" href="{{ route($routeName) }}">
                    <svg>
                        <use href="#sb-i-{{ $icon }}" />
                    </svg>
                    <span>{{ $label }}</span>
                    @if($label === 'Reservations' && $hasPending)
                        <i class="sb-side-dot"></i>
                    @endif
                </a>
            @endforeach

            {{-- MY CART: each role can bundle gowns into one booking --}}
            @if($cartRole && ($sectionLabel === 'OPERATIONS' || ($role === 'customer' && $sectionLabel === 'MY ACCOUNT')))
                <a class="sb-side-link {{ request()->routeIs('*.cart') ? 'is-active' : '' }}"
                    href="{{ route($cartRole . '.cart') }}">
                    <svg>
                        <use href="#sb-i-cart" />
                    </svg>
                    <span>My Cart</span>
                    @if($cartCount > 0)
                        <span class="sb-notification-count">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                    @endif
                </a>
            @endif

            {{-- Inventory dropdown (owner only) --}}
            @if($role === 'owner' && $sectionLabel === 'BUSINESS')
                <details class="sb-inventory-nav" {{ request()->routeIs(...$inventoryRoutes) ? 'open' : '' }}>
                    <summary class="sb-side-link {{ request()->routeIs(...$inventoryRoutes) ? 'is-active' : '' }}">
                        <svg>
                            <use href="#sb-i-dress" />
                        </svg>
                        <span>Inventory</span>
                        <span class="sb-inventory-chevron">⌄</span>
                    </summary>

                    <div class="sb-inventory-subnav">
                        <a class="{{ request()->routeIs('owner.gowns.*') ? 'is-active' : '' }}"
                            href="{{ route('owner.gowns.index') }}">Gowns</a>
                        <a class="{{ request()->routeIs('owner.categories.*') ? 'is-active' : '' }}"
                            href="{{ route('owner.categories.index') }}">Categories</a>
                        <a class="{{ request()->routeIs('owner.accessories.*') ? 'is-active' : '' }}"
                            href="{{ route('owner.accessories.index') }}">Accessories</a>
                        <a class="{{ request()->routeIs('owner.maintenance') ? 'is-active' : '' }}"
                            href="{{ route('owner.maintenance') }}">Maintenance</a>
                        <a class="{{ request()->routeIs('owner.damages*') ? 'is-active' : '' }}"
                            href="{{ route('owner.damages') }}">Damage reports</a>
                        <a class="{{ request()->routeIs('owner.purchases*') ? 'is-active' : '' }}"
                            href="{{ route('owner.purchases') }}">Purchases</a>
                    </div>
                </details>
            @endif
        </nav>
    @endforeach

    {{-- BOTTOM: profile + user --}}
    <div class="sb-sidebar-bottom">
        <a class="sb-side-link {{ request()->routeIs('profile.edit') ? 'is-active' : '' }}"
            href="{{ route('profile.edit') }}">
            <svg>
                <use href="#sb-i-user" />
            </svg>
            <span>My profile</span>
        </a>

        <div class="sb-side-user">
            <span class="sb-side-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="sb-side-user-copy">
                <b>{{ auth()->user()->name }}</b>
                <small>{{ ucfirst($role) }} account</small>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sb-logout" title="Sign out" aria-label="Sign out">
                    <svg>
                        <use href="#sb-i-logout" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<button class="sb-sidebar-scrim" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen=false"
    aria-label="Close navigation">
</button>