<x-app-layout>
    <div class="sb-page sb-reserve-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div><span class="sb-kicker">RESERVATION {{ $reservation->reservation_code }}</span>
                    <h1>Your <em>reservation.</em></h1>
                    <p>Track your booking from pickup through return.</p>
                </div>
                <a class="sb-outline-btn" href="{{ route('customer.reservations') }}">All reservations</a>
            </div>

            @if(session('success'))
            <div class="sb-success">{{ session('success') }}</div>@endif
            @if($errors->any())
            <div class="sb-form-errors">{{ $errors->first() }}</div>@endif

            @php
                $gown = $reservation->items->first()?->gown;
                $pending = (float) $reservation->payments->where('status', 'pending')->sum('amount');
                $paid = (float) $reservation->amount_paid;
                $paidDownPayment = (float) $reservation->payments
                    ->where('payment_type', 'downpayment')
                    ->where('status', 'verified')
                    ->sum('amount');
                $penaltyTotal = (float) $reservation->penalties->where('status', '!=', 'waived')->sum('amount');
            @endphp

            <div class="sb-reserve-layout">
                <aside class="sb-reserve-summary">
                    @if($gown)
                        <a class="sb-reserve-summary-photo @if(!$gown->image) is-empty @endif" @if($gown->image)
                        style="background-image:url('{{ asset('storage/' . $gown->image) }}')" @endif
                            href="{{ route('customer.gowns.show', $gown) }}" aria-label="{{ $gown->name }}"></a>
                    @endif
                    <div class="sb-reserve-summary-body">
                        <span class="sb-kicker">YOUR GOWN</span>
                        <h2>{{ $gown?->name ?? 'Gown booking' }}</h2>
                        <p>{{ $gown?->category->name ?? 'The Collection' }} · Size {{ $gown?->size ?? 'Various' }}</p>
                        <div class="sb-reserve-summary-rows">
                            <div><span>Reservation #</span><b>{{ $reservation->reservation_code }}</b></div>
                            <div><span>Booked on</span><b>{{ $reservation->created_at?->toFormattedDateString() }}</b>
                            </div>
                        </div>
                        <div class="sb-reserve-summary-total"><span>Rental
                                fee</span><b>₱{{ number_format($reservation->rental_total, 2) }}</b></div>
                        <div class="sb-reserve-summary-note">Bring one valid government-issued ID when you collect the
                            gown. Staff will keep it securely as a record and return it after the gown is returned.
                        </div>
                    </div>
                </aside>

                <div class="sb-reserve-main">
                    <div class="sb-reserve-card">
                        @if($reservation->status === 'cancelled')
                            <div class="sb-cancellation-receipt" role="status">
                                <span class="sb-kicker">RESERVATION UPDATE</span>
                                <h2>Reservation Cancelled</h2>
                                <p>Your reservation for {{ $gown?->name ?? 'this gown' }} has been cancelled.</p>
                                <div class="sb-cancellation-totals">
                                    <div><span>Down payment</span><b>₱{{ number_format($paidDownPayment, 2) }}</b></div>
                                    <div><span>Refund</span><b>₱0.00</b></div>
                                </div>
                                <p class="sb-cancellation-policy">
                                    {{ $paidDownPayment > 0 ? 'Your down payment was retained according to the cancellation policy.' : 'No verified down payment was recorded for this reservation.' }}
                                </p>
                                <a class="sb-btn" href="{{ route('customer.reservations') }}">Back to My Reservations</a>
                            </div>
                        @else
                            <div class="sb-confirm-hero">
                                <span class="sb-confirm-tick">✓</span>
                                <h2>Reservation submitted</h2>
                                <p>{{ $gown?->name }} is requested. Staff will verify your agreement and payment before it
                                    is confirmed.</p>
                                <span class="sb-status sb-status-pending"><span class="sb-status-dot is-pending"></span>
                                    Pending Approval</span>
                            </div>
                        @endif

                        @if($reservation->status !== 'cancelled')
                            <h3 class="sb-section-title">Reservation lifecycle</h3>
                            <ol class="sb-timeline">
                                @foreach($lifecycle as $step)
                                    <li class="is-{{ $step['state'] }}">
                                        <span
                                            class="sb-timeline-dot">{{ $step['state'] === 'done' ? '✓' : ($step['state'] === 'current' ? '•' : '') }}</span>
                                        <b>{{ $step['label'] }}</b>
                                        @if($step['meta'])<small>{{ $step['meta'] }}</small>@endif
                                    </li>
                                @endforeach
                            </ol>

                            <h3 class="sb-section-title">Booking details</h3>
                            <div class="sb-confirm-list">
                                <div><span>Pickup</span><b>{{ $reservation->pickup_date?->toFormattedDateString() }}</b>
                                </div>
                                <div><span>Return</span><b>{{ $reservation->return_date?->toFormattedDateString() }}</b>
                                </div>
                                @if($reservation->customer_notes)
                                    <div><span>Notes</span><b>{{ $reservation->customer_notes }}</b></div>
                                @endif
                                <div>
                                    <span>Agreement</span><b>{{ $reservation->agreement_accepted_at ? 'Accepted' : 'Pending' }}</b>
                                </div>
                                <div><span>ID
                                        collateral</span><b>{{ ucfirst(str_replace('_', ' ', $reservation->collateral_status ?? 'not received')) }}</b>
                                </div>
                            </div>
                            @if($reservation->measurements)
                                <p class="sb-form-intro">Recorded measurements: {{ $reservation->measurements }}</p>
                            @endif

                            <h3 class="sb-section-title">Payment summary</h3>
                            <div class="sb-payment-breakdown">
                                <div><span>Rental fee</span><b>₱{{ number_format($reservation->rental_total, 2) }}</b></div>
                                @if($penaltyTotal > 0)
                                <div><span>Penalties</span><b>₱{{ number_format($penaltyTotal, 2) }}</b></div>@endif
                                <div>
                                    <span>Total</span><b>₱{{ number_format($reservation->grand_total + $penaltyTotal, 2) }}</b>
                                </div>
                                <div><span>Paid to date</span><b>₱{{ number_format($paid, 2) }}</b></div>
                                <div>
                                    <span>Balance</span><b>₱{{ number_format(max(0, (float) $reservation->balance), 2) }}</b>
                                </div>
                            </div>

                            <h3 class="sb-section-title">Payment history</h3>
                            @forelse($reservation->payments->sortBy('created_at') as $payment)
                                <div class="sb-customer-payment-row">
                                    <span>
                                        <b>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</b>
                                        <small>{{ $payment->created_at->toFormattedDateString() }} ·
                                            {{ strtoupper($payment->payment_method) }} ·
                                            {{ $payment->payment_reference }}</small>
                                    </span>
                                    <span>
                                        ₱{{ number_format($payment->amount, 2) }}
                                        <em class="sb-pay-{{ $payment->status }}">{{ ucfirst($payment->status) }}</em>
                                        @if($payment->proof_of_payment)<a href="{{ route('payments.proof', $payment) }}"
                                        target="_blank" rel="noopener">View proof</a>@endif
                                    </span>
                                </div>
                            @empty
                                <p class="sb-payment-empty">No payments submitted yet.</p>
                            @endforelse

                            @if($reservation->penalties->isNotEmpty())
                                <h3 class="sb-section-title">Penalties</h3>
                                @foreach($reservation->penalties as $penalty)
                                    <div class="sb-customer-payment-row">
                                        <span><b>{{ $penalty->penalty_type }}</b><small>{{ ucfirst($penalty->status) }}</small></span>
                                        <span>₱{{ number_format($penalty->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            @endif

                            <div class="sb-step-controls">
                                @if(in_array($reservation->status, ['pending', 'awaiting_payment'], true))
                                    <button class="sb-small-btn sb-reject-btn" type="button" data-cancel-open
                                        data-cancel-action="{{ route('customer.reservations.cancel', $reservation) }}"
                                        data-cancel-gown="{{ $gown?->name ?? 'Gown reservation' }}"
                                        data-cancel-payment="{{ number_format($paidDownPayment, 2, '.', '') }}">
                                        Cancel Reservation
                                    </button>
                                @endif
                                <a class="sb-btn" href="{{ route('customer.reservations') }}">View Reservation</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('customer.partials.reservation-cancellation-dialog')
</x-app-layout>