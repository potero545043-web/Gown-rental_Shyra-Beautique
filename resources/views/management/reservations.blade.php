@php
    $filtering = request()->filled('q') || request()->filled('status');
@endphp

<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <h1>Reservation <em>desk.</em></h1>
                    <p>Review guest requests, update gown handoffs, and record payments.</p>
                </div>
                {{-- Add reservation: pick a gown in the catalog, then reserve for the walk-in customer --}}
                <a class="sb-btn" href="{{ route($base . '.catalog') }}">Add reservation +</a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            <form class="sb-filterbar" method="GET" data-live-filter>
                <input type="hidden" name="per_page" value="{{ $reservations->perPage() }}">
                <input name="q" value="{{ request('q') }}" placeholder="Search guest or reservation">
                <select name="status">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending Approval</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Approved</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <optgroup label="Other statuses">
                        @foreach(['ready_for_pickup', 'released', 'returned', 'rejected', 'overdue'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <button class="sb-btn" type="submit">Filter</button>
                @if($filtering)
                    <a class="sb-filter-clear" href="{{ route($base . '.reservations') }}">Clear</a>
                @endif
            </form>

            <section class="sb-panel sb-reservations-panel">
                <div class="sb-table-wrap sb-ops-table-wrap">
                    <table class="sb-table sb-ops-table sb-ops-table--reservations">
                        <thead>
                            <tr>
                                <th>RESERVATION</th>
                                <th>CUSTOMER</th>
                                <th>GOWN</th>
                                <th>RENTAL DATES</th>
                                <th>TOTAL / BALANCE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations as $reservation)
                                <tr class="sb-clickable-row"
                                    data-href="{{ route($base . '.reservations.show', $reservation) }}"
                                    tabindex="0" role="link"
                                    aria-label="View reservation {{ $reservation->reservation_code }}">
                                    <td data-label="Reservation">
                                        <strong>{{ $reservation->reservation_code }}</strong>
                                        <small class="sb-cell-sub">{{ $reservation->created_at?->format('M d, Y') }}</small>
                                    </td>
                                    <td data-label="Customer">
                                        <strong>{{ $reservation->customer->full_name ?? 'Guest' }}</strong>
                                        <small
                                            class="sb-cell-sub">{{ $reservation->customer->contact_number ?? 'No contact' }}</small>
                                        <small class="sb-cell-sub">{{ $reservation->customer->email ?? '' }}</small>
                                    </td>
                                    <td data-label="Gown">
                                        {{ $reservation->items->map(fn($item) => $item->gown?->name)->filter()->join(', ') ?: 'Gown' }}
                                    </td>
                                    <td data-label="Rental dates">{{ $reservation->pickup_date?->format('M d') }} –
                                        {{ $reservation->return_date?->format('M d, Y') }}
                                    </td>
                                    <td data-label="Total / balance">
                                        <strong>&#8369;{{ number_format($reservation->grand_total, 2) }}</strong>
                                        <small class="sb-cell-sub">Balance
                                            &#8369;{{ number_format($reservation->balance, 2) }}</small>
                                    </td>
                                    <td data-label="Status"><span
                                            class="sb-status sb-status-{{ $reservation->status }}">{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</span>
                                        @if($reservation->status === 'cancelled')
                                            @php
                                                $retainedDownPayment = (float) $reservation->payments
                                                    ->where('payment_type', 'downpayment')
                                                    ->where('status', 'verified')
                                                    ->sum('amount');
                                            @endphp
                                            <small class="sb-cell-sub">Customer cancelled ·
                                                ₱{{ number_format($retainedDownPayment, 2) }} retained</small>
                                        @endif
                                    </td>
                                    <td data-label="Actions">
                                        <details class="sb-row-actions">
                                            <summary aria-label="Manage reservation">Manage</summary>
                                            <div class="sb-row-actions-panel">
                                                <button type="button" class="sb-row-actions-close" aria-label="Close reservation actions">×</button>
                                                {{-- customers.show exists for the owner only --}}
                                                @if($reservation->customer && $base === 'owner')
                                                    <a class="sb-small-btn"
                                                        href="{{ route($base . '.customers.show', $reservation->customer) }}">View
                                                        Reservation History</a>
                                                @endif
                                                <form method="POST"
                                                    action="{{ route($base . '.reservations.update', $reservation) }}"
                                                    class="sb-row-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    @php
                                                        $statusChoices = match ($reservation->status) {
                                                            'pending', 'awaiting_payment' => ['pending', 'confirmed', 'cancelled', 'rejected'],
                                                            'confirmed' => ['confirmed', 'ready_for_pickup', 'cancelled', 'rejected'],
                                                            'ready_for_pickup' => ['ready_for_pickup', 'cancelled'],
                                                            'returned' => ['returned', 'completed'],
                                                            default => [$reservation->status],
                                                        };
                                                    @endphp
                                                    <label>Status
                                                        <select name="status">
                                                            @foreach($statusChoices as $status)
                                                                <option value="{{ $status }}"
                                                                    @selected($reservation->status === $status)>
                                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>Staff note
                                                        <input name="admin_notes" placeholder="Optional note"
                                                            value="{{ $reservation->admin_notes }}">
                                                    </label>
                                                    <div class="sb-form-row">
                                                        <label>Pickup date
                                                            <input type="date" name="pickup_date"
                                                                value="{{ $reservation->pickup_date?->format('Y-m-d') }}" required>
                                                        </label>
                                                        <label>Return date
                                                            <input type="date" name="return_date"
                                                                value="{{ $reservation->return_date?->format('Y-m-d') }}" required>
                                                        </label>
                                                    </div>
                                                    <button class="sb-small-btn">Update booking</button>
                                                </form>
                                                @if($reservation->balance > 0 && !in_array($reservation->status, ['cancelled', 'rejected', 'completed'], true))
                                                    <form method="POST"
                                                        action="{{ route($base . '.payments.store', $reservation) }}"
                                                        class="sb-row-action-form">
                                                        @csrf
                                                        <strong>Record cash payment</strong>
                                                        <input type="number" min="0.01" step="0.01"
                                                            max="{{ $reservation->balance }}" name="amount"
                                                            placeholder="Amount (PHP)" required>
                                                        <select name="payment_type">
                                                            <option value="downpayment">Down payment</option>
                                                            <option value="rental_balance">Rental balance</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                        <select name="payment_method">
                                                            <option value="cash">Cash</option>
                                                        </select>
                                                        <button class="sb-small-btn">Save payment</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="sb-empty sb-empty-state">
                                        <div class="sb-res-empty">
                                            <span class="sb-res-empty-icon">◷</span>
                                            @if($filtering)
                                                <strong>No reservations match your filters</strong>
                                                <small>Try a different search or status.</small>
                                                <a class="sb-res-empty-link" href="{{ route($base . '.reservations') }}">Clear
                                                    filters</a>
                                            @else
                                                <strong>No reservations yet</strong>
                                                <small>Customer booking requests will appear here for review.</small>
                                                <a class="sb-btn" href="{{ route($base . '.catalog') }}">Browse catalog</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @include('components.table-pagination', ['paginator' => $reservations, 'itemLabel' => 'reservations'])
        </div>
    </div>

    <script>
        (() => {
            const closeOpenActions = () => document.querySelectorAll('.sb-row-actions[open]').forEach(item => item.open = false);
            document.addEventListener('click', event => {
                const closeButton = event.target.closest('.sb-row-actions-close');
                if (closeButton) {
                    closeButton.closest('.sb-row-actions').open = false;
                    return;
                }
                if (!event.target.closest('.sb-row-actions')) closeOpenActions();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeOpenActions();
            });
        })();
    </script>

    <style>
        .sb-filterbar {
            align-items: center;
        }

        .sb-filterbar input,
        .sb-filterbar select {
            border: 1px solid #d9b8a0;
            background: #fffaf5;
        }

        .sb-filter-clear {
            color: #6d1935;
            font-size: 13px;
            font-weight: 600;
            text-decoration: underline;
            white-space: nowrap;
        }

        .sb-res-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 26px 12px;
        }

        .sb-res-empty-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #5e0f27;
            color: #fbe9d0;
            font-size: 18px;
        }

        .sb-res-empty strong {
            font-size: 15px;
        }

        .sb-res-empty small {
            max-width: 340px;
        }

        .sb-res-empty .sb-btn {
            margin-top: 6px;
        }

        .sb-res-empty-link {
            color: #6d1935;
            font-weight: 600;
            text-decoration: underline;
        }
    </style>
</x-app-layout>
