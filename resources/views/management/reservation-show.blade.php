<x-app-layout>
    <div class="sb-page sb-reservation-details-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">RESERVATION {{ $reservation->reservation_code }}</span>
                    <h1>Reservation <em>details.</em></h1>
                    <p>Review the booking, customer, gowns, and payment history.</p>
                </div>
                <a class="sb-outline-btn" href="{{ route($base . '.reservations') }}">Back to reservations</a>
            </div>

            <section class="sb-panel sb-reservation-details">
                <div class="sb-reservation-details-head">
                    <div>
                        <span class="sb-kicker">BOOKING STATUS</span>
                        <h2>{{ $reservation->reservation_code }}</h2>
                    </div>
                    <span class="sb-status sb-status-{{ $reservation->status }}">
                        {{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                    </span>
                </div>

                <div class="sb-reservation-details-grid sb-reservation-customer-grid">
                    <div><small>CUSTOMER</small><b>{{ $reservation->customer->full_name ?? 'Guest' }}</b></div>
                    <div><small>CONTACT</small><b>{{ $reservation->customer->contact_number ?? 'No contact' }}</b></div>
                    <div><small>EMAIL</small><b>{{ $reservation->customer->email ?? 'Not provided' }}</b></div>
                    <div><small>PICKUP DATE</small><b>{{ $reservation->pickup_date?->toFormattedDateString() ?? '—' }}</b></div>
                    <div><small>RETURN DATE</small><b>{{ $reservation->return_date?->toFormattedDateString() ?? '—' }}</b></div>
                    <div><small>BOOKED ON</small><b>{{ $reservation->created_at?->toFormattedDateString() ?? '—' }}</b></div>
                </div>

                <h3 class="sb-section-title sb-reservation-section-title">Reserved gowns</h3>
                <div class="sb-table-wrap sb-reservation-gowns-wrap">
                    <table class="sb-table sb-reservation-gowns-table">
                        <thead>
                            <tr>
                                <th>PHOTO</th>
                                <th>GOWN</th>
                                <th>CATEGORY</th>
                                <th>SIZE</th>
                                <th>RENTAL PRICE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservation->items as $item)
                                <tr>
                                    <td data-label="Photo">
                                        <div class="sb-reservation-gown-photo">
                                            @if($item->gown)
                                                <img src="{{ $item->gown->image_url }}"
                                                    alt="{{ $item->gown->name }}" loading="lazy">
                                            @else
                                                <span aria-hidden="true">Gown unavailable</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Gown">
                                        <strong>{{ $item->gown?->name ?? 'Gown no longer available' }}</strong>
                                        <small>{{ $item->gown?->gown_code ?? '—' }}</small>
                                    </td>
                                    <td>{{ $item->gown?->category?->name ?? '—' }}</td>
                                    <td>{{ $item->gown?->size ?: '—' }}</td>
                                    <td>₱{{ number_format((float) ($item->rental_price ?? $item->gown?->rental_price ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No gown items are attached to this reservation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 class="sb-section-title sb-reservation-section-title">Payment summary</h3>
                <div class="sb-reservation-details-grid sb-reservation-payment-grid">
                    <div><small>RENTAL TOTAL</small><b>₱{{ number_format((float) $reservation->rental_total, 2) }}</b></div>
                    <div><small>GRAND TOTAL</small><b>₱{{ number_format((float) $reservation->grand_total, 2) }}</b></div>
                    <div><small>PAID</small><b>₱{{ number_format((float) $reservation->amount_paid, 2) }}</b></div>
                    <div><small>BALANCE</small><b>₱{{ number_format((float) $reservation->balance, 2) }}</b></div>
                    <div><small>AGREEMENT</small><b>{{ $reservation->agreement_accepted_at ? 'Accepted' : 'Pending' }}</b></div>
                    <div><small>ID COLLATERAL</small><b>{{ ucfirst(str_replace('_', ' ', $reservation->collateral_status ?? 'not received')) }}</b></div>
                </div>

                @if($reservation->customer_notes)
                    <h3 class="sb-section-title">Customer notes</h3>
                    <p>{{ $reservation->customer_notes }}</p>
                @endif

                @if($reservation->admin_notes)
                    <h3 class="sb-section-title">Staff notes</h3>
                    <p>{{ $reservation->admin_notes }}</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
