@php
    $lateFee = (float) old('late_fee_per_day', $settings->get('late_fee_per_day', 0));
@endphp

<x-app-layout>
    <div class="min-h-screen bg-[#F7EFE4] text-[#2E2A26]">
        <div class="relative max-w-7xl mx-auto px-5 py-8 lg:px-8">

            <div class="mb-8">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[.18em] text-[#9A7A42]">Shyra Beautique · Settings</p>
                <h1 class="font-display text-3xl md:text-4xl font-semibold text-[#5C1A2B]">Boutique <em class="text-[#C9A46A]">settings.</em></h1>
                <p class="mt-2 text-sm text-[#766B70]">Keep the boutique details and rental policies up to date.</p>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            <section class="sb-panel sb-settings-card">
                <form method="POST" action="{{ route('owner.settings.save') }}" class="sb-reservation-form">
                    @csrf
                    @method('PUT')

                    <div class="sb-settings-section">
                        <h2 class="font-display">Business details</h2>
                        <p class="sb-form-intro">How your boutique appears to staff and customers.</p>

                        <label>
                            Boutique name
                            <input name="shop_name"
                                value="{{ old('shop_name', $settings->get('shop_name', 'Shyra Beautique')) }}" required>
                        </label>

                        <div class="sb-form-row">
                            <label>
                                Contact email
                                <input type="email" name="contact_email" placeholder="hello@shyrabeautique.com"
                                    value="{{ old('contact_email', $settings->get('contact_email')) }}">
                            </label>

                            <label>
                                Contact number
                                <input name="contact_number" placeholder="09XX XXX XXXX"
                                    value="{{ old('contact_number', $settings->get('contact_number')) }}">
                            </label>
                        </div>
                    </div>

                    <div class="sb-settings-section">
                        <h2 class="font-display">Rental policy</h2>
                        <p class="sb-form-intro">Applied automatically when staff record a return.</p>

                        <label class="sb-settings-narrow">
                            Late return fee per day
                            <span class="sb-money">
                                <input type="number" min="0.01" step="0.01" name="late_fee_per_day"
                                    value="{{ old('late_fee_per_day', $settings->get('late_fee_per_day', '0')) }}"
                                    required>
                            </span>
                            <small class="sb-settings-hint">Charged for every day an item is returned late.</small>
                        </label>

                        @if($lateFee <= 0)
                            <div class="sb-setting-warning" role="alert">
                                <span aria-hidden="true">!</span>
                                <div>
                                    <strong>Late fee is not set</strong>
                                    Configure a positive daily rate before accepting new reservations.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="sb-settings-footer">
                        <button class="sb-inventory-primary" type="submit">Save boutique settings</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <style>
        .sb-heading p {
            max-width: 720px;
        }

        /* Card: same tokens as .sb-panel, centered at a readable width */
        html body .sb-settings-card {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 24px;
            background: #fff;
            border: 1px solid #DCCBB2;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(46, 42, 38, .06);
            font-family: "DM Sans", sans-serif;
        }

        html body .sb-settings-card .sb-reservation-form {
            max-width: none;
            width: 100%;
        }

        /* Section titles: same as Overview / inventory titles */
        .sb-settings-section h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #5c1a2b;
            text-align: left;
        }

        .sb-settings-section .sb-form-intro {
            margin: -8px 0 4px;
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #8b6f61;
            text-align: left;
        }

        /* Labels, inputs */
        html body .sb-settings-card label {
            margin: 0;
            font-family: "DM Sans", sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: #5c1a2b;
        }

        html body .sb-settings-card input {
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            font-weight: 400;
            color: #4a3037;
            background: #fffdf9;
            border: 1px solid #e7d9cc;
        }

        html body .sb-settings-card input::placeholder {
            color: #a99c8f;
        }

        html body .sb-settings-card input:focus {
            outline: none;
            border-color: #5c1a2b;
            box-shadow: 0 0 0 3px rgba(92, 26, 43, .12);
        }

        html body .sb-settings-hint {
            font-family: "DM Sans", sans-serif;
            font-size: 13px;
            font-weight: 400;
            color: #a99c8f;
        }

        /* Sections */
        .sb-settings-section {
            display: grid;
            gap: 12px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eaded3;
        }

        .sb-settings-section .sb-form-row {
            margin: 0;
        }

        .sb-settings-section:has(+ .sb-settings-footer) {
            border-bottom: 0;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .sb-settings-narrow {
            max-width: 320px;
        }

        .sb-settings-narrow .sb-settings-hint {
            display: block;
            margin-top: 8px;
        }

        /* Peso input */
        .sb-money {
            position: relative;
            display: block;
        }

        .sb-money::before {
            content: "₱";
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #5c1a2b;
            font-size: 16px;
            font-weight: 600;
            pointer-events: none;
        }

        html body .sb-settings-card .sb-money input {
            padding-left: 34px;
        }

        /* Warning */
        .sb-setting-warning {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin: 4px 0 6px;
            padding: 12px 14px;
            border: 1px solid #e6c36a;
            border-radius: 12px;
            background: #fbf0d3;
            color: #6b4e12;
            font-family: "DM Sans", sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        .sb-setting-warning>span {
            flex: 0 0 22px;
            height: 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #b8860b;
            color: #fff;
            font-weight: 800;
            font-size: 13px;
        }

        .sb-setting-warning strong {
            display: block;
            color: #4e3708;
        }

        /* Footer */
        .sb-settings-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 0;
        }

        @media (max-width: 640px) {
            .sb-settings-footer {
                justify-content: stretch;
            }

            .sb-settings-footer .sb-inventory-primary {
                width: 100%;
            }

            .sb-settings-narrow {
                max-width: none;
            }
        }
    </style>
</x-app-layout>
