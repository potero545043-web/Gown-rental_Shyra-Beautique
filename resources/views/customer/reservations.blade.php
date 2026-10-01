<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">YOUR BOOKINGS</span>
                    <h1>My <em>reservations.</em></h1>
                    <p>View reservation status, rental dates, and payments.</p>
                </div>
                <a class="sb-btn" href="{{ route('customer.catalog') }}">Browse gowns</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <div class="sb-customer-bookings">
                @forelse($reservations as $reservation)
                    @php
                        $pendingAmount = $reservation->payments->where('status', 'pending')->sum('amount');
                        $payableNow = max(0, (float) $reservation->balance - $pendingAmount);
                        $lifecycle = $lifecycles[$reservation->id] ?? [];
                        $doneCount = collect($lifecycle)->where('state', 'done')->count();
                        $gown = $reservation->items->first()?->gown;
                        $paidDownPayment = (float) $reservation->payments
                            ->where('payment_type', 'downpayment')
                            ->where('status', 'verified')
                            ->sum('amount');
                    @endphp
                    <article class="sb-panel sb-customer-booking">
                        <div class="sb-customer-booking-head">
                            <div>
                                <span class="sb-kicker">{{ $reservation->reservation_code }}</span>
                                <h2>{{ $gown?->name ?? 'Gown booking' }}</h2>
                                <small>{{ $reservation->pickup_date?->toFormattedDateString() }} –
                                    {{ $reservation->return_date?->toFormattedDateString() }}</small>
                            </div>
                            <span
                                class="sb-status sb-status-{{ $reservation->status }}">{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</span>
                        </div>

                        @if($gown?->image)
                            <a class="sb-customer-booking-thumb"
                                style="background-image:url('{{ asset('storage/' . $gown->image) }}')"
                                href="{{ route('customer.reservations.show', $reservation) }}"
                                aria-label="{{ $gown->name }}"></a>
                        @endif

                        <div class="sb-customer-progress">
                            <div class="sb-customer-progress-head">
                                <b>Booking progress</b>
                                <span>{{ $doneCount }} of {{ count($lifecycle) }} steps complete</span>
                            </div>
                            <div class="sb-progress-track">
                                <div class="sb-progress-fill"
                                    style="width: {{ count($lifecycle) ? round($doneCount / count($lifecycle) * 100) : 0 }}%">
                                </div>
                            </div>
                            <ol class="sb-lifecycle-chips">
                                @foreach($lifecycle as $step)
                                    <li class="is-{{ $step['state'] }}" title="{{ $step['meta'] }}">
                                        <span>{{ $step['state'] === 'done' ? '✓' : '' }}</span>{{ $step['label'] }}
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        <div class="sb-customer-booking-bottom">
                            <div><small>RENTAL FEE</small><b>₱{{ number_format($reservation->rental_total, 2) }}</b></div>
                            <div><small>PICKUP
                                    ID</small><b>{{ $reservation->collateral_status === 'released' ? 'Returned' : ($reservation->collateral_status === 'held' ? 'Held until return' : 'Required at pickup') }}</b>
                            </div>
                            <div><small>PAID</small><b>₱{{ number_format($reservation->amount_paid, 2) }}</b></div>
                            <div><small>BALANCE</small><b>₱{{ number_format($reservation->balance, 2) }}</b></div>
                            <div>
                                <small>PAYMENT</small><b>{{ $reservation->status === 'cancelled' && $paidDownPayment > 0 ? 'Payment retained' : ($reservation->balance <= 0 ? 'Paid' : ($reservation->amount_paid > 0 ? 'Partially paid' : ($pendingAmount > 0 ? 'Payment pending' : 'Payment due'))) }}</b>
                            </div>
                            <div>
                                <small>AGREEMENT</small><b>{{ $reservation->agreement_accepted_at ? 'Accepted' : 'Pending' }}</b>
                            </div>
                        </div>
                        @if($reservation->measurements)
                        <p class="sb-form-intro">Recorded measurements: {{ $reservation->measurements }}</p>@endif

                        <div class="sb-customer-booking-actions">
                            <a class="sb-btn" href="{{ route('customer.reservations.show', $reservation) }}">View
                                Details</a>
                            @if(in_array($reservation->status, ['pending', 'awaiting_payment'], true))
                                <button class="sb-small-btn sb-reject-btn" type="button" data-cancel-open
                                    data-cancel-action="{{ route('customer.reservations.cancel', $reservation) }}"
                                    data-cancel-gown="{{ $gown?->name ?? 'Gown reservation' }}"
                                    data-cancel-payment="{{ number_format($paidDownPayment, 2, '.', '') }}">
                                    Cancel Reservation
                                </button>
                            @endif
                        </div>

                        <details class="sb-customer-booking-details">
                            <summary>Payment details &amp; submit proof</summary>
                            <div class="sb-customer-payment-layout">
                                <div>
                                    <h3>Payment history</h3>
                                    @forelse($reservation->payments as $payment)
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
                                                @if($payment->proof_of_payment)
                                                    <a href="{{ route('payments.proof', $payment) }}" target="_blank"
                                                        rel="noopener">View proof</a>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="sb-payment-empty">No payments submitted yet.</p>
                                    @endforelse
                                </div>

                                @if($payableNow > 0 && !in_array($reservation->status, ['cancelled', 'rejected', 'completed'], true))
                                    <form method="POST"
                                        action="{{ route('customer.reservations.payments.store', $reservation) }}"
                                        enctype="multipart/form-data" class="sb-customer-payment-form"
                                        data-customer-payment-form>
                                        @csrf
                                        <h3>Submit a payment</h3>
                                        <p>Cash payments remain pending until staff confirm them at the boutique.</p>
                                        <label>Payment type
                                            <select name="payment_type" required>
                                                <option value="downpayment">Down payment</option>
                                                <option value="rental_balance">Rental balance</option>
                                                <option value="penalty">Penalty</option>
                                                <option value="damage_fee">Damage fee</option>
                                            </select>
                                        </label>
                                        <input type="hidden" name="payment_method" value="cash">
                                        <p>Payment method <b>Cash</b></p>
                                        <label>Amount (up to ₱{{ number_format($payableNow, 2) }})
                                            <input type="number" name="amount" min="0.01" max="{{ $payableNow }}" step="0.01"
                                                required>
                                        </label>
                                        <small>A down payment must be greater than ₱500.00.</small>
                                        <button class="sb-small-btn">Submit cash payment for confirmation</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="sb-panel sb-empty">No reservations yet. Browse the catalog to request a gown.</div>
                @endforelse
            </div>
        </div>
    </div>
    @include('customer.partials.reservation-cancellation-dialog')
</x-app-layout>