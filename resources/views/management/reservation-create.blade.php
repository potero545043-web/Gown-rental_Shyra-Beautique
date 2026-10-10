<x-app-layout>
    <div class="sb-page sb-reserve-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">IN-STORE BOOKING</span>
                    <h1>Reserve <em>{{ $cartGowns->count() }}</em> gown{{ $cartGowns->count() === 1 ? '' : 's' }}.</h1>
                    <p>Complete the five steps to record this in-store reservation.</p>
                </div>
                <a class="sb-outline-btn" href="{{ route(auth()->user()->role . '.catalog') }}">Back to collection</a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors" role="alert">{{ $errors->first() }}</div>
            @endif
            @if($lateFeePerDay <= 0)
                <div class="sb-form-errors" role="alert">Set a positive daily late fee in Settings before accepting reservations.</div>
            @endif

            <div class="sb-reserve-layout">
                <aside class="sb-reserve-summary">
                    <div class="sb-reserve-summary-body">
                        <span class="sb-kicker">SELECTED GOWNS</span>
                        <h2>{{ $cartGowns->count() }} gown{{ $cartGowns->count() === 1 ? '' : 's' }}</h2>
                        <p>One reservation · one agreement · one payment</p>
                        <div class="sb-cart-list">
                            @foreach($cartGowns as $cartGown)
                                <div class="sb-cart-item">
                                    <a class="sb-cart-item-photo"
                                        style="background-image:url('{{ $cartGown->image_url }}')"
                                        href="{{ route(auth()->user()->role . '.catalog.show', $cartGown) }}"
                                        aria-label="{{ $cartGown->name }}"></a>
                                    <div class="sb-cart-item-info">
                                        <b>{{ $cartGown->name }}</b>
                                        <small>{{ $cartGown->gown_code }} · {{ $cartGown->size ?? 'Various' }}</small>
                                        <span class="sb-cart-item-price">₱{{ number_format((float) $cartGown->rental_price, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="sb-reserve-summary-rows">
                            <div><span>Gowns</span><b>{{ $cartGowns->count() }}</b></div>
                            <div><span>Daily late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }}</b></div>
                            <div><span>Maximum rental</span><b>3 days</b></div>
                        </div>
                        <div class="sb-reserve-summary-total">
                            <span>Rental total</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b>
                        </div>
                        <div class="sb-reserve-summary-note">Add any other gowns the customer needs for the same dates — they are saved as one reservation with a single agreement and payment.</div>
                    </div>
                </aside>

                <div class="sb-reserve-main">
                    <form method="POST" action="{{ route(auth()->user()->role . '.reservations.store') }}"
                        enctype="multipart/form-data" class="sb-reservation-form" data-reservation-wizard>
                        @csrf
                        @foreach($cartGowns as $cartGown)
                            <input type="hidden" name="gown_ids[]" value="{{ $cartGown->id }}"
                                data-price="{{ (float) $cartGown->rental_price }}" @if($loop->first) data-primary-gown @endif required>
                        @endforeach

                        <div class="sb-progress-head">
                            <div class="sb-progress-meta">
                                <b data-progress-label>Step 1 of 5 · Customer &amp; dates</b>
                                <span data-progress-percent>20% complete</span>
                            </div>
                            <div class="sb-progress-track" role="progressbar" aria-label="Reservation progress"
                                aria-valuemin="1" aria-valuemax="5" aria-valuenow="1">
                                <div class="sb-progress-fill" data-progress-fill style="width:20%"></div>
                            </div>
                        </div>

                        <ol class="sb-step-dots" aria-label="Reservation steps">
                            <li data-step-indicator class="is-active"><span>1</span><b>Customer &amp; dates</b></li>
                            <li data-step-indicator><span>2</span><b>Gown &amp; sizing</b></li>
                            <li data-step-indicator><span>3</span><b>Agreement &amp; ID</b></li>
                            <li data-step-indicator><span>4</span><b>Payment</b></li>
                            <li data-step-indicator><span>5</span><b>Confirmation</b></li>
                        </ol>

                        <div class="sb-reserve-card">
                            <div class="sb-form-errors" data-step-error role="alert" hidden></div>

                            <section class="sb-flow-step">
                                <span class="sb-kicker">STEP 1</span>
                                <h2>Customer &amp; dates</h2>
                                <label>Existing customer account
                                    <select name="existing_customer_id">
                                        <option value="">Guest / new customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('existing_customer_id') == $customer->id)>
                                                {{ $customer->full_name }} · {{ $customer->contact_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="sb-form-row">
                                    <label>Guest full name
                                        <input name="customer_name" value="{{ old('customer_name') }}"
                                            placeholder="Required for a new guest">
                                    </label>
                                    <label>Guest contact number
                                        <input name="contact_number" value="{{ old('contact_number') }}"
                                            placeholder="Required for a new guest">
                                    </label>
                                </div>
                                <label>Guest email
                                    <input type="email" name="customer_email" value="{{ old('customer_email') }}">
                                </label>
                                <div class="sb-form-row">
                                    <label>Pickup date
                                        <input id="pickup-date" type="date" name="pickup_date"
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('pickup_date') }}" required>
                                    </label>
                                    <label>Return date
                                        <input id="return-date" type="date" name="return_date"
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('return_date') }}" required>
                                    </label>
                                </div>
                                <p class="sb-form-intro">Rental is up to 3 days from pickup, subject to availability.</p>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 2</span>
                                <h2>Gowns &amp; sizing</h2>
                                <div class="sb-selected-gowns">
                                    @foreach($cartGowns as $cartGown)
                                        <div class="sb-selected-gown" data-gown-row="{{ $cartGown->id }}">
                                            <b>{{ $cartGown->name }}</b>
                                            <small>{{ $cartGown->gown_code }} · {{ $cartGown->size ?? 'Various sizes' }}</small>
                                            <strong>₱{{ number_format((float) $cartGown->rental_price, 2) }}</strong>
                                            @if($cartGowns->count() > 1)
                                                <button type="button" class="sb-cart-item-remove" data-remove-gown-btn
                                                    data-gown-id="{{ $cartGown->id }}"
                                                    aria-label="Remove {{ $cartGown->name }}">×</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if($addableGowns->isNotEmpty())
                                    <label>Add another gown to this reservation (same dates)
                                        <select data-add-gown>
                                            <option value="">Choose a gown to add…</option>
                                            @foreach($addableGowns as $option)
                                                <option value="{{ $option->id }}" data-name="{{ $option->name }}"
                                                    data-code="{{ $option->gown_code }}"
                                                    data-size="{{ $option->size ?? 'Various sizes' }}"
                                                    data-price="{{ (float) $option->rental_price }}">
                                                    {{ $option->name }} · {{ $option->gown_code }} · ₱{{ number_format((float) $option->rental_price, 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button type="button" class="sb-outline-btn" data-add-gown-btn>Add this gown</button>
                                @endif

                                <p class="sb-form-intro">Record the customer’s measurements in centimeters to help prepare the fit.</p>
                                <div class="sb-form-row">
                                    <label>Bust (cm)
                                        <input name="bust" type="number" min="0" max="300" step="0.1" value="{{ old('bust') }}">
                                    </label>
                                    <label>Waist (cm)
                                        <input name="waist" type="number" min="0" max="300" step="0.1" value="{{ old('waist') }}">
                                    </label>
                                </div>
                                <div class="sb-form-row">
                                    <label>Hips (cm)
                                        <input name="hips" type="number" min="0" max="300" step="0.1" value="{{ old('hips') }}">
                                    </label>
                                    <label>Length (cm)
                                        <input name="length" type="number" min="0" max="400" step="0.1" value="{{ old('length') }}">
                                    </label>
                                </div>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 3</span>
                                <h2>Rental agreement &amp; ID custody</h2>
                                <label>Live photo of valid physical government ID
                                    <input type="file" name="government_id" accept="image/jpeg,image/png,image/webp"
                                        capture="environment" required>
                                    <small class="sb-field-hint">JPG, PNG or WEBP, up to 4 MB.</small>
                                </label>
                                <label>Secure safe slot
                                    <input name="id_safe_slot" value="{{ old('id_safe_slot') }}"
                                        placeholder="Example: Safe A · Slot 04" required>
                                </label>
                                <details class="sb-agreement">
                                    <summary><b>Review the rental terms</b><span aria-hidden="true">VIEW</span></summary>
                                    <ol>
                                        <li>The renter provides one valid government-issued ID at pickup.</li>
                                        <li>The shop keeps the physical ID securely and returns it when the gown is returned. No cash security deposit is charged.</li>
                                        <li>Do not wash, dry-clean, or alter the gown; the shop handles professional cleaning.</li>
                                        <li>A late fee of ₱{{ number_format($lateFeePerDay, 2) }} accrues for every late day.</li>
                                        <li>The renter is financially responsible for repair costs or the replacement value if the gown is ruined or lost.</li>
                                    </ol>
                                </details>
                                <label class="sb-agreement-check">
                                    <input type="checkbox" name="agreement_accepted" value="1" required
                                        @checked(old('agreement_accepted'))>
                                    <span>Customer has read and agreed to the rental terms.</span>
                                </label>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 4</span>
                                <h2>Payment</h2>
                                <div class="sb-payment-breakdown">
                                    <h3>Payment breakdown</h3>
                                    <div><span>Rental fee (all gowns)</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b></div>
                                    <div><span>Total amount due</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b></div>
                                </div>
                                <label>Amount collected now (PHP)
                                    <input type="number" name="payment_amount" min="0.01"
                                        max="{{ (float) $cartGowns->sum(fn($g) => (float) $g->rental_price) }}" step="0.01"
                                        value="{{ old('payment_amount', (float) $cartGowns->sum(fn($g) => (float) $g->rental_price)) }}" required
                                        data-payment-amount>
                                    <small>Collect the full rental total or a down payment greater than ₱500.00.</small>
                                </label>
                                <label>Payment method
                                    <select name="payment_method" required>
                                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                                    </select>
                                </label>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 5</span>
                                <h2>Confirmation</h2>
                                <p class="sb-form-intro">Review the details before saving this in-store reservation.</p>
                                <div class="sb-confirm-list">
                                    <section class="sb-confirm-group">
                                        <h3>Reservation</h3>
                                        <div><span>Customer</span><b data-confirm="customer">Guest / new customer</b></div>
                                        <div><span>Gowns</span><b data-confirm="gowns">{{ $cartGowns->map(fn($g) => $g->name . ' · ' . $g->gown_code)->join(' + ') }}</b></div>
                                        <div><span>Pickup</span><b data-confirm="pickup_date">—</b></div>
                                        <div><span>Return</span><b data-confirm="return_date">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Payment</h3>
                                        <div><span>Payment method</span><b>Cash</b></div>
                                        <div><span>Amount collected now</span><b data-confirm="payment_amount">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Required before saving</h3>
                                        <div><span>ID photo</span><b>Required</b></div>
                                        <div><span>Safe slot</span><b data-confirm="id_safe_slot">—</b></div>
                                        <div><span>Agreement</span><b>Customer accepted</b></div>
                                    </section>
                                </div>
                            </section>

                            <div class="sb-step-controls">
                                <button type="button" class="sb-outline-btn" data-step-back disabled>Back</button>
                                <span data-step-count>Step 1 of 5</span>
                                <button type="button" class="sb-btn" data-step-next>Continue</button>
                                <button class="sb-btn sb-btn-confirm" type="submit" data-step-submit hidden>Save agreement and payment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-reservation-wizard]');
            const steps = [...form.querySelectorAll('.sb-flow-step')];
            const indicators = [...form.querySelectorAll('[data-step-indicator]')];
            const errors = @json(array_keys($errors->getMessages()));
            const labels = ['Customer & dates', 'Gowns & sizing', 'Agreement & ID', 'Payment', 'Confirmation'];
            const existing = form.querySelector('[name="existing_customer_id"]');
            const guestName = form.querySelector('[name="customer_name"]');
            const guestPhone = form.querySelector('[name="contact_number"]');
            const pickup = form.querySelector('[name="pickup_date"]');
            const returned = form.querySelector('[name="return_date"]');
            const paymentAmount = form.querySelector('[name="payment_amount"]');
            const safeSlot = form.querySelector('[name="id_safe_slot"]');
            let current = steps.findIndex(step =>
                errors.some(name => step.querySelector(`[name="${name}"]`))
            );

            if (current < 0) current = 0;

            const peso = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const money = n => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(n));

            /* ---- Multiple gowns in one reservation ---- */
            const addGownSelect = form.querySelector('[data-add-gown]');
            const addGownBtn = form.querySelector('[data-add-gown-btn]');
            const gownsContainer = form.querySelector('.sb-selected-gowns');
            const gownNames = [];

            const refreshGownTotals = () => {
                const total = [...form.querySelectorAll('input[name="gown_ids[]"]')].reduce((sum, input) => {
                    return sum + Number(input.dataset.price || 0);
                }, 0);
                form.querySelectorAll('[data-total-display]').forEach(el => el.textContent = peso(total));
                paymentAmount.max = total.toFixed(2);
                if (!paymentAmount.value || Number(paymentAmount.value) > total) {
                    paymentAmount.value = total.toFixed(2);
                }
                form.querySelector('[data-confirm="gowns"]').textContent = gownNames.length
                    ? gownNames.join(' + ')
                    : '—';
                updateSummary();
            };

            const addGownRow = (id, name, code, size, price) => {
                const row = document.createElement('div');
                row.className = 'sb-selected-gown';
                row.innerHTML = '<b></b><small></small><strong></strong>';
                row.querySelector('b').textContent = name;
                row.querySelector('small').textContent = code + ' · ' + size;
                row.querySelector('strong').textContent = peso(price);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'gown_ids[]';
                hidden.value = id;
                hidden.dataset.price = price;
                row.appendChild(hidden);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'sb-cart-item-remove';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'Remove ' + name);
                remove.addEventListener('click', () => {
                    const index = gownNames.indexOf(name + ' · ' + code);
                    if (index > -1) gownNames.splice(index, 1);
                    row.remove();
                    refreshGownTotals();
                });
                row.appendChild(remove);

                gownsContainer.appendChild(row);
                gownNames.push(name + ' · ' + code);
            };

            // Seed the list with the gown(s) already carried over from the cart.
            form.querySelectorAll('[data-gown-row]').forEach(row => {
                const hidden = row.querySelector('input[name="gown_ids[]"]');
                gownNames.push(row.querySelector('b').textContent + ' · ' + row.querySelector('small').textContent.split(' · ')[0]);
            });

            // Let staff drop a gown straight from this list before saving.
            form.querySelectorAll('[data-remove-gown-btn]').forEach(button => {
                button.addEventListener('click', () => {
                    const row = button.closest('[data-gown-row]');
                    const hidden = row.querySelector('input[name="gown_ids[]"]');
                    if (hidden) hidden.remove();
                    row.remove();
                    refreshGownTotals();
                });
            });

            if (addGownBtn && addGownSelect) {
                addGownBtn.addEventListener('click', () => {
                    const opt = addGownSelect.selectedOptions[0];
                    if (!opt || !opt.value) { addGownSelect.reportValidity(); return; }
                    if (form.querySelector('input[name="gown_ids[]"][value="' + opt.value + '"]')) {
                        addGownSelect.value = '';
                        return;
                    }
                    addGownRow(opt.value, opt.dataset.name, opt.dataset.code, opt.dataset.size, opt.dataset.price);
                    addGownSelect.value = '';
                    refreshGownTotals();
                });
            }

            const setGuestRequired = () => {
                guestName.required = guestPhone.required = !existing.value;
            };

            const updateSummary = () => {
                const customer = existing.value
                    ? existing.selectedOptions[0].textContent.trim()
                    : (guestName.value.trim() || 'Guest / new customer');
                form.querySelector('[data-confirm="customer"]').textContent = customer;
                form.querySelector('[data-confirm="pickup_date"]').textContent = pickup.value || '—';
                form.querySelector('[data-confirm="return_date"]').textContent = returned.value || '—';
                form.querySelector('[data-confirm="payment_amount"]').textContent = paymentAmount.value
                    ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(paymentAmount.value))
                    : '—';
                form.querySelector('[data-confirm="id_safe_slot"]').textContent = safeSlot.value.trim() || '—';
            };

            const show = () => {
                steps.forEach((step, index) => step.hidden = index !== current);
                indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('is-active', index === current);
                    indicator.classList.toggle('is-complete', index < current);
                });

                const percent = Math.round(((current + 1) / steps.length) * 100);
                form.querySelector('[data-step-count]').textContent = `Step ${current + 1} of ${steps.length}`;
                form.querySelector('[data-progress-label]').textContent = `Step ${current + 1} of ${steps.length} · ${labels[current]}`;
                form.querySelector('[data-progress-percent]').textContent = `${percent}% complete`;
                form.querySelector('[data-progress-fill]').style.width = `${percent}%`;
                form.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', String(current + 1));
                form.querySelector('[data-step-back]').disabled = current === 0;
                form.querySelector('[data-step-next]').hidden = current === steps.length - 1;
                form.querySelector('[data-step-submit]').hidden = current !== steps.length - 1;
                updateSummary();
            };

            existing.addEventListener('change', setGuestRequired);
            [guestName, pickup, returned, paymentAmount, safeSlot].forEach(field => {
                field.addEventListener('input', updateSummary);
                field.addEventListener('change', updateSummary);
            });
            setGuestRequired();
            refreshGownTotals();

            form.querySelector('[data-step-back]').addEventListener('click', () => {
                if (current > 0) {
                    current--;
                    show();
                }
            });

            form.querySelector('[data-step-next]').addEventListener('click', () => {
                const invalid = [...steps[current].querySelectorAll('input,select,textarea')]
                    .find(field => !field.checkValidity());

                if (invalid) {
                    invalid.reportValidity();
                    return;
                }

                if (current < steps.length - 1) {
                    current++;
                    show();
                }
            });

            const dateLimit = () => {
                if (!pickup.value) return;

                const [year, month, day] = pickup.value.split('-').map(Number);
                const max = new Date(Date.UTC(year, month - 1, day + 2)).toISOString().slice(0, 10);
                returned.min = pickup.value;
                returned.max = max;

                // Auto-set the return date to the last rental day; staff can still
                // shorten it to any date within the window.
                if (!returned.value || returned.value < pickup.value || returned.value > max) {
                    returned.value = max;
                }

                updateSummary();
            };

            pickup.addEventListener('change', dateLimit);
            dateLimit();
            show();
        })();
    </script>
</x-app-layout>
