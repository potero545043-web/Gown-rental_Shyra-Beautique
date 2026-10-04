<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div><span class="sb-kicker">IN-STORE BOOKING</span>
                    <h1>New <em>reservation.</em></h1>
                    <p>Complete the three steps for the selected gown.</p>
                </div><a class="sb-outline-btn" href="{{ route(auth()->user()->role . '.catalog.show', $gown) }}">Back
                    to gown</a>
            </div>
            @if($errors->any())
            <div class="sb-form-errors">{{ $errors->first() }}</div>@endif
            @if($lateFeePerDay <= 0)
                <div class="sb-form-errors">Set a positive daily late fee in Settings before accepting reservations.</div>
            @endif
            <form method="POST" action="{{ route(auth()->user()->role . '.reservations.store') }}"
                enctype="multipart/form-data" class="sb-reservation-form sb-panel sb-customer-flow"
                data-reservation-wizard>@csrf
                <ol class="sb-step-progress" aria-label="Reservation steps">
                    <li data-step-indicator><span>1</span><b>Customer &amp; dates</b></li>
                    <li data-step-indicator><span>2</span><b>Gown &amp; sizing</b></li>
                    <li data-step-indicator><span>3</span><b>ID custody, agreement &amp; payment</b></li>
                </ol>
                <section class="sb-flow-step"><span class="sb-kicker">STEP 1</span>
                    <h2>Customer &amp; dates</h2>
                    <label>Existing customer account<select name="existing_customer_id">
                            <option value="">Guest / new customer</option>@foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('existing_customer_id') == $customer->id)>
                                    {{ $customer->full_name }} · {{ $customer->contact_number }}
                            </option>@endforeach
                        </select></label>
                    <div class="sb-form-row"><label>Guest full name<input name="customer_name"
                                value="{{ old('customer_name') }}"
                                placeholder="Required for a new guest"></label><label>Guest contact number<input
                                name="contact_number" value="{{ old('contact_number') }}"
                                placeholder="Required for a new guest"></label></div>
                    <label>Guest email<input type="email" name="customer_email"
                            value="{{ old('customer_email') }}"></label>
                    <div class="sb-form-row"><label>Pickup date<input id="pickup-date" type="date" name="pickup_date"
                                min="{{ today()->format('Y-m-d') }}" value="{{ old('pickup_date') }}"
                                required></label><label>Return date<input id="return-date" type="date"
                                name="return_date" min="{{ today()->format('Y-m-d') }}" value="{{ old('return_date') }}"
                                required></label></div>
                    <p class="sb-form-intro">Rental period is up to 3 days from pickup, subject to availability.</p>
                </section>
                <section class="sb-flow-step"><span class="sb-kicker">STEP 2</span>
                    <h2>Gown &amp; sizing</h2>
                    <input type="hidden" name="gown_id" value="{{ $gown->id }}" required>
                    <div class="sb-selected-gown"><b>{{ $gown->name }}</b><small>{{ $gown->gown_code }} ·
                            {{ $gown->size ?? 'Various sizes' }}</small><strong>Rental fee:
                            ₱{{ number_format($gown->rental_price, 2) }}</strong></div>
                    <p class="sb-form-intro">Take and record the customer’s measurements in centimeters.</p>
                    <div class="sb-form-row"><label>Bust<input name="bust" type="number" min="0" max="300" step="0.1"
                                value="{{ old('bust') }}"></label><label>Waist<input name="waist" type="number" min="0"
                                max="300" step="0.1" value="{{ old('waist') }}"></label></div>
                    <div class="sb-form-row"><label>Hips<input name="hips" type="number" min="0" max="300" step="0.1"
                                value="{{ old('hips') }}"></label><label>Length<input name="length" type="number"
                                min="0" max="400" step="0.1" value="{{ old('length') }}"></label></div>
                </section>
                <section class="sb-flow-step"><span class="sb-kicker">STEP 3</span>
                    <h2>ID custody, agreement &amp; payment</h2>
                    <label>Live photo of valid physical government ID<input type="file" name="government_id"
                            accept="image/jpeg,image/png,image/webp" capture="environment" required></label>
                    <label>Secure safe slot<input name="id_safe_slot" value="{{ old('id_safe_slot') }}"
                            placeholder="Example: Safe A · Slot 04" required></label>
                    <div class="sb-agreement">
                        <h3>Rental agreement</h3>
                        <ol>
                            <li>Renter provides one valid government-issued ID at pickup.</li>
                            <li>The shop keeps the physical ID securely as a record and returns it when the gown is
                                returned. No cash security deposit is charged.</li>
                            <li>Do not wash, dry-clean, or alter the gown. The shop handles professional cleaning.</li>
                            <li>A late fee of ₱{{ number_format($lateFeePerDay, 2) }} accrues for every late day.</li>
                            <li>Renter is financially responsible for repair costs or the full replacement value if the
                                gown is ruined or lost.</li>
                        </ol><label class="sb-agreement-check"><input type="checkbox" name="agreement_accepted"
                                value="1" required> Customer has read and agreed to the rental terms.</label>
                    </div>
                    <div class="sb-payment-breakdown">
                        <h3>Payment breakdown</h3>
                        <div><span>Rental fee</span><b>₱{{ number_format($gown->rental_price, 2) }}</b></div>
                        <div><span>Total amount due</span><b>₱{{ number_format($gown->rental_price, 2) }}</b></div>
                    </div>
                    <label>Amount collected now (PHP)<input type="number" name="payment_amount" min="0.01"
                            max="{{ $gown->rental_price }}" step="0.01"
                            value="{{ old('payment_amount', $gown->rental_price) }}" required><small>Collect full
                            payment or a non-refundable down payment.</small></label>
                    <label>Payment method<select name="payment_method" required>
                            <option value="cash">Cash</option>
                        </select></label>
                </section>
                <div class="sb-step-controls"><button type="button" class="sb-outline-btn"
                        data-step-back>Back</button><span data-step-count>Step 1 of 3</span><button type="button"
                        class="sb-btn" data-step-next>Next</button><button class="sb-btn" type="submit" data-step-submit
                        hidden>Save agreement and payment</button></div>
            </form>
        </div>
    </div>
    {{-- Return date limit is pickup + 2 days (a 3-day rental), matching storeEmployeeReservation. --}}
    <script>(() => { const form = document.querySelector('[data-reservation-wizard]'), steps = [...form.querySelectorAll('.sb-flow-step')], indicators = [...form.querySelectorAll('[data-step-indicator]')], errors = @json(array_keys($errors->getMessages())); let current = steps.findIndex(step => errors.some(name => step.querySelector(`[name="${name}"]`))); if (current < 0) current = 0; const existing = form.querySelector('[name=existing_customer_id]'), guestName = form.querySelector('[name=customer_name]'), guestPhone = form.querySelector('[name=contact_number]'), setGuestRequired = () => { guestName.required = guestPhone.required = !existing.value; }; existing.addEventListener('change', setGuestRequired); setGuestRequired(); const show = () => { steps.forEach((step, index) => step.hidden = index !== current); indicators.forEach((item, index) => { item.classList.toggle('is-active', index === current); item.classList.toggle('is-complete', index < current); }); form.querySelector('[data-step-count]').textContent = `Step ${current + 1} of ${steps.length}`; form.querySelector('[data-step-back]').disabled = current === 0; form.querySelector('[data-step-next]').hidden = current === steps.length - 1; form.querySelector('[data-step-submit]').hidden = current !== steps.length - 1; }; form.querySelector('[data-step-back]').addEventListener('click', () => { if (current > 0) { current--; show(); } }); form.querySelector('[data-step-next]').addEventListener('click', () => { const invalid = [...steps[current].querySelectorAll('input,select,textarea')].find(field => !field.checkValidity()); if (invalid) { invalid.reportValidity(); return; } if (current < steps.length - 1) { current++; show(); } }); show(); const pickup = document.getElementById('pickup-date'), returned = document.getElementById('return-date'); const dateLimit = () => { if (!pickup.value) return; const [y, m, d] = pickup.value.split('-').map(Number), max = new Date(Date.UTC(y, m - 1, d + 2)).toISOString().slice(0, 10); returned.min = pickup.value; returned.max = max; if (returned.value && (returned.value < pickup.value || returned.value > max)) returned.value = ''; }; pickup.addEventListener('change', dateLimit); dateLimit(); })();</script>
</x-app-layout>