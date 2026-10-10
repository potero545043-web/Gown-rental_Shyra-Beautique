<x-app-layout>
    <div class="sb-page sb-customer-reservations-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">YOUR BOOKINGS</span>
                    <h1>My <em>reservations.</em></h1>
                    <p>View reservation status, rental dates, and payments. Select a row to see its details.</p>
                </div>
                <a class="sb-btn" href="{{ route('customer.catalog') }}">Browse gowns</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-customer-reservations-panel">
                <div class="sb-table-wrap sb-customer-reservations-table-wrap">
                    <table class="sb-table sb-customer-reservations-table">
                        <thead>
                            <tr>
                                <th>RESERVATION</th>
                                <th>GOWNS</th>
                                <th>RENTAL DATES</th>
                                <th>TOTAL / BALANCE</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations as $reservation)
                                @php
                                    $pendingAmount = (float) $reservation->payments->where('status', 'pending')->sum('amount');
                                    $gown = $reservation->items->first()?->gown;
                                    $gownCount = $reservation->items->count();
                                    $gownNames = $reservation->items->map(fn($item) => $item->gown?->name)->filter()->join(', ');
                                    $paidDownPayment = (float) $reservation->payments
                                        ->where('payment_type', 'downpayment')
                                        ->where('status', 'verified')
                                        ->sum('amount');
                                    $paymentState = $reservation->status === 'cancelled' && $paidDownPayment > 0
                                        ? 'Payment retained'
                                        : ($reservation->balance <= 0
                                            ? 'Paid'
                                            : ($reservation->amount_paid > 0
                                                ? 'Partially paid'
                                                : ($pendingAmount > 0 ? 'Payment pending' : 'Payment due')));
                                @endphp
                                <tr class="sb-clickable-row"
                                    data-href="{{ route('customer.reservations.show', $reservation) }}"
                                    tabindex="0" role="link"
                                    aria-label="View reservation {{ $reservation->reservation_code }}">
                                    <td data-label="Reservation">
                                        <strong>{{ $reservation->reservation_code }}</strong>
                                        <small class="sb-cell-sub">{{ $reservation->created_at?->format('M d, Y') }}</small>
                                    </td>
                                    <td data-label="Gowns">
                                        <strong>{{ $gownCount }} {{ \Illuminate\Support\Str::plural('gown', $gownCount) }}</strong>
                                        <small class="sb-cell-sub">{{ $gownNames ?: 'Gown details unavailable' }}</small>
                                    </td>
                                    <td data-label="Rental dates">
                                        {{ $reservation->pickup_date?->format('M d, Y') }} –
                                        {{ $reservation->return_date?->format('M d, Y') }}
                                    </td>
                                    <td data-label="Total / balance">
                                        <strong>₱{{ number_format((float) $reservation->grand_total, 2) }}</strong>
                                        <small class="sb-cell-sub">Balance ₱{{ number_format((float) $reservation->balance, 2) }}</small>
                                    </td>
                                    <td data-label="Status">
                                        <span class="sb-status sb-status-{{ $reservation->status }}">
                                            {{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                                        </span>
                                        <small class="sb-cell-sub">{{ $paymentState }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="sb-empty sb-empty-state">
                                        <div class="sb-res-empty">
                                            <strong>No reservations yet</strong>
                                            <small>Browse the collection to request a gown.</small>
                                            <a class="sb-btn" href="{{ route('customer.catalog') }}">Browse gowns</a>
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
    @include('customer.partials.reservation-cancellation-dialog')
</x-app-layout>
