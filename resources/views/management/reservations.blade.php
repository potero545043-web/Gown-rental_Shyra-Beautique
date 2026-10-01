<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <h1>Reservation <em>desk.</em></h1>
                    <p>Review guest requests, update gown handoffs, and record payments.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            <form class="sb-filterbar" method="GET">
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
                                <tr>
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
                                            class="sb-status">{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</span>
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
                                                @if($reservation->customer)
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
                                        <strong>No reservations yet</strong>
                                        <small>Customer booking requests will appear here for review.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="sb-pagination">{{ $reservations->links() }}</div>
        </div>
    </div>
</x-app-layout>