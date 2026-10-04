@php
    $lateFee = (float) old('late_fee_per_day', $settings->get('late_fee_per_day', 0));
@endphp

<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">SHYRA BEAUTIQUE · SETTINGS</span>
                    <h1>Boutique <em>settings.</em></h1>
                    <p>Keep the boutique details and rental policies up to date.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            <section class="sb-panel sb-settings-card">
                <form method="POST" action="{{ route('owner.settings.save') }}" class="sb-reservation-form">
                    @csrf
                    @method('PUT')

                    <h2>Business details</h2>
                    <p class="sb-form-intro">These details help staff apply consistent rental policies.</p>

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

                    <label>
                        Late return fee per day
                        <span class="sb-money">
                            <input type="number" min="0.01" step="0.01" name="late_fee_per_day"
                                value="{{ old('late_fee_per_day', $settings->get('late_fee_per_day', '0')) }}" required>
                        </span>
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

                    <small class="sb-settings-hint">
                        A late fee is added for every late day when staff record the return.
                    </small>

                    <button class="sb-btn" type="submit">Save boutique settings</button>
                </form>
            </section>
        </div>
    </div>

    <style>
        .sb-heading p { max-width: 720px; }
        html body .sb-settings-card { width: 100%; max-width: 760px; margin-left: auto; margin-right: auto; }

        html body .sb-settings-card {
            background: #fffaf5;
            border: 1px solid #d9b8a0;
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(94, 15, 39, .08);
        }

        html body .sb-settings-card input {
            border: 1px solid #d9b8a0;
            background: #fff;
        }

        html body .sb-settings-hint { color: #7a6642; font-size: 13px; }

        .sb-money { position: relative; display: block; }
        .sb-money::before {
            content: "₱";
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #7a6642;
            font-weight: 600;
            pointer-events: none;
        }
        html body .sb-settings-card .sb-money input { padding-left: 34px; }

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
            font-size: 13px;
            line-height: 1.5;
        }
        .sb-setting-warning > span {
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
        .sb-setting-warning strong { display: block; color: #4e3708; }
    </style>
</x-app-layout>
