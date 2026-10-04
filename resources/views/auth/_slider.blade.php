@php
    $mode = $mode ?? 'login';
    $isReg = $mode === 'register';
@endphp

<div class="sa-stage">

    {{-- soft gold decoration --}}
    <div class="sa-decor" aria-hidden="true">
        <i class="sa-ring" style="top:-96px;right:-96px;width:288px;height:288px"></i>
        <i class="sa-ring" style="top:-64px;right:-64px;width:224px;height:224px"></i>
        <i class="sa-ring" style="bottom:-96px;left:-96px;width:288px;height:288px"></i>
    </div>

    <div class="sa-card" id="saCard" data-mode="{{ $mode }}">

        {{-- FORMS (register on the left, login on the right) --}}
        <div class="sa-forms">

            {{-- REGISTER --}}
            <section class="sa-form sa-form-register" data-form="register"
                aria-hidden="{{ $isReg ? 'false' : 'true' }}">
                <div class="sa-head">
                    <span class="sa-kicker">New customer account</span>
                    <h2>Create an <em>account</em></h2>
                    <p>Enter your details to get started.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="sa-fields">
                    @csrf

                    <div class="sa-field sa-wide">
                        <label for="reg-name">Your name</label>
                        <input id="reg-name" type="text" name="name" value="{{ $isReg ? old('name') : '' }}" required
                            autocomplete="name" placeholder="Enter your full name">
                        @if($isReg)<x-input-error :messages="$errors->get('name')" class="mt-2" />@endif
                    </div>

                    <div class="sa-field sa-wide">
                        <label for="reg-email">Email address</label>
                        <input id="reg-email" type="email" name="email" value="{{ $isReg ? old('email') : '' }}"
                            required autocomplete="username" placeholder="you@example.com">
                        @if($isReg)<x-input-error :messages="$errors->get('email')" class="mt-2" />@endif
                    </div>

                    <div class="sa-field">
                        <label for="reg-password">Create a password</label>
                        <input id="reg-password" type="password" name="password" required autocomplete="new-password"
                            placeholder="At least 8 characters">
                        @if($isReg)<x-input-error :messages="$errors->get('password')" class="mt-2" />@endif
                    </div>

                    <div class="sa-field">
                        <label for="reg-password-confirmation">Confirm password</label>
                        <input id="reg-password-confirmation" type="password" name="password_confirmation" required
                            autocomplete="new-password" placeholder="Enter your password again">
                        @if($isReg)<x-input-error :messages="$errors->get('password_confirmation')"
                        class="mt-2" />@endif
                    </div>

                    <button type="submit" class="sa-submit luxury-button">Create my account</button>
                </form>

                <p class="sa-switch">Already have an account?
                    <a href="{{ route('login') }}" data-auth-switch="login">Log In</a>
                </p>
            </section>

            {{-- LOGIN --}}
            <section class="sa-form sa-form-login" data-form="login" aria-hidden="{{ $isReg ? 'true' : 'false' }}">
                <div class="sa-head">
                    <span class="sa-kicker">Shyra Beautique</span>
                    <h2>Welcome <em>back.</em></h2>
                    <p>Enter your account details to continue.</p>
                </div>

                @unless($isReg)
                    <x-auth-session-status class="mb-4" :status="session('status')" />
                @endunless

                <form method="POST" action="{{ route('login') }}" class="sa-fields">
                    @csrf

                    <div class="sa-field">
                        <label for="login-email">Email address</label>
                        <input id="login-email" type="email" name="email" value="{{ $isReg ? '' : old('email') }}"
                            required autocomplete="username" placeholder="you@example.com">
                        @unless($isReg)<x-input-error :messages="$errors->get('email')" class="mt-2" />@endunless
                    </div>

                    <div class="sa-field">
                        <div class="sa-row">
                            <label for="login-password">Password</label>
                            @if (Route::has('password.request'))
                                <a class="sa-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>
                        <div class="sa-pass">
                            <input id="login-password" type="password" name="password" required
                                autocomplete="current-password" placeholder="Enter your password">
                            <button type="button" class="sa-eye" data-toggle-password="login-password"
                                aria-label="Show or hide password">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        @unless($isReg)<x-input-error :messages="$errors->get('password')" class="mt-2" />@endunless
                    </div>

                    <label class="sa-remember">
                        <input type="checkbox" name="remember"> <span>Remember me</span>
                    </label>

                    <button type="submit" class="sa-submit luxury-button">
                        <span>Log In</span> <span aria-hidden="true">→</span>
                    </button>
                </form>

                @if (Route::has('register'))
                    <p class="sa-switch">New to Shyra Beautique?
                        <a href="{{ route('register') }}" data-auth-switch="register">Create an account</a>
                    </p>
                @endif
            </section>
        </div>

        {{-- SLIDING PANEL (gown photo under a maroon fade) --}}
        <aside class="sa-panel">
            <div class="sa-brand">
                <img src="{{ asset('images/Logo.png') }}" alt="Shyra Beautique" class="sb-logo-lockup">
                <span>Gown Reservation &amp; Rental</span>
            </div>

            <div class="sa-copies">
                <div class="sa-copy sa-copy-login">
                    <div class="sa-eyebrow"><u></u> Welcome Back</div>
                    <h1>Your perfect gown <span>awaits.</span></h1>
                    <p>Log in to manage your reservations, browse elegant gowns, and enjoy a simple rental experience
                        with Shyra Beautique.</p>
                </div>
                <div class="sa-copy sa-copy-register">
                    <div class="sa-eyebrow"><u></u> Join Us</div>
                    <h1>Your next look <span>starts here.</span></h1>
                    <p>Create an account to browse gowns, request reservation dates, and keep track of your bookings.
                    </p>
                </div>
            </div>

            <div class="sa-foot">Elegant looks. Easy reservations.</div>
        </aside>
    </div>
</div>

<style>
    :root {
        --sa-cream: #f8f0d6;
        --sa-cream-soft: #fbf5e3;
        --sa-input: #fffbf0;
        --sa-maroon: #6b0020;
        --sa-maroon-dark: #4f0018;
        --sa-gold: #a67c2e;
        --sa-gold-line: #d9c089;
        --sa-field-border: #a67c2e;
        --sa-text: #4a3b35;
        --sa-muted: #7a6a60;
    }

    /* force cream even if the guest layout sets its own (pink) background */
    html:has(.sa-stage),
    body:has(.sa-stage),
    body:has(.sa-stage)>div {
        background: var(--sa-cream) !important;
    }

    .sa-stage {
        background: var(--sa-cream);
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow: hidden;
    }

    .sa-decor {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .sa-decor i {
        position: absolute;
    }

    .sa-decor .sa-ring {
        border: 1px solid rgba(166, 124, 46, .3);
        border-radius: 50%;
    }

    .sa-card {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 1080px;
        background: var(--sa-cream-soft);
        border: 1px solid var(--sa-gold-line);
        border-radius: 2rem;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(107, 0, 32, .14);
    }

    .sa-forms {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 500px;
    }

    /* forms */
    .sa-form {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 32px 44px;
        opacity: 0;
        visibility: hidden;
        transition: opacity .3s ease, visibility 0s linear .3s;
    }

    .sa-card[data-mode="login"] .sa-form-login,
    .sa-card[data-mode="register"] .sa-form-register {
        opacity: 1;
        visibility: visible;
        transition: opacity .45s ease .3s, visibility 0s;
    }

    .sa-head {
        margin-bottom: 16px;
    }

    .sa-kicker {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        color: var(--sa-gold);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .sa-kicker::before {
        content: "";
        width: 32px;
        height: 1px;
        background: var(--sa-gold);
    }

    .sa-head h2 {
        margin: 0;
        color: var(--sa-maroon);
        font: 600 34px/1.1 'Playfair Display', serif;
    }

    .sa-head h2 em {
        color: var(--sa-gold);
        font-weight: 500;
    }

    .sa-head p {
        margin: 8px 0 0;
        color: var(--sa-muted);
        font-size: 14px;
    }

    .sa-fields {
        display: grid;
        gap: 12px;
    }

    .sa-form-register .sa-fields {
        grid-template-columns: 1fr 1fr;
        gap: 12px 14px;
    }

    .sa-form-register .sa-submit,
    .sa-form-register .sa-wide {
        grid-column: 1 / -1;
    }

    .sa-field label {
        display: block;
        margin-bottom: 6px;
        color: var(--sa-maroon);
        font-size: 14px;
        font-weight: 600;
    }

    .sa-field input:not([type="checkbox"]) {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 14px;
        border: 1px solid var(--sa-field-border);
        border-radius: 14px;
        background: var(--sa-input);
        color: var(--sa-text);
        font-size: 14px;
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .sa-field input::placeholder {
        color: #a39383;
        opacity: 1;
    }

    .sa-field input:focus {
        border-color: var(--sa-field-border);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(166, 124, 46, .18);
    }

    /* same border on every field (login + register), whatever the layout or Tailwind forms add */
    .sa-card .sa-field input:not([type="checkbox"]) {
        border: 1px solid var(--sa-gold-line) !important;
        box-shadow: none;
    }

    .sa-card .sa-field input:not([type="checkbox"]):focus {
        border-color: var(--sa-gold) !important;
        box-shadow: 0 0 0 3px rgba(166, 124, 46, .18);
    }

    .sa-field input:-webkit-autofill {
        -webkit-box-shadow: 0 0 0 1000px var(--sa-input) inset;
        -webkit-text-fill-color: var(--sa-text);
    }

    .sa-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sa-row label {
        margin-bottom: 8px;
    }

    .sa-forgot {
        margin-bottom: 8px;
        color: var(--sa-maroon);
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
    }

    .sa-forgot:hover {
        text-decoration: underline;
    }

    .sa-pass {
        position: relative;
    }

    .sa-pass input {
        padding-right: 48px !important;
    }

    .sa-eye {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        display: grid;
        place-items: center;
        padding: 4px;
        border: 0;
        background: none;
        color: var(--sa-gold);
        cursor: pointer;
    }

    .sa-eye:hover {
        color: var(--sa-maroon);
    }

    .sa-remember {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--sa-muted);
        font-size: 14px;
        cursor: pointer;
    }

    .sa-remember input {
        accent-color: var(--sa-maroon);
    }

    /* main button: solid maroon pill */
    .sa-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 13px 24px;
        border: 0;
        border-radius: 999px;
        background: var(--sa-maroon);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(107, 0, 32, .2);
        transition: background .15s;
    }

    .sa-submit:hover {
        background: var(--sa-maroon-dark);
    }

    /* switch link: outlined gold pill, like "Create Account" on the welcome page */
    .sa-switch {
        display: grid;
        justify-items: center;
        gap: 8px;
        margin: 14px 0 0;
        text-align: center;
        color: var(--sa-muted);
        font-size: 14px;
    }

    .sa-switch a {
        display: block;
        width: 100%;
        padding: 10px 24px;
        border: 1px solid var(--sa-gold);
        border-radius: 999px;
        color: var(--sa-maroon);
        font-weight: 600;
        text-decoration: none;
        transition: background .15s;
    }

    .sa-switch a:hover {
        background: rgba(166, 124, 46, .1);
    }

    .sa-submit:focus-visible,
    .sa-switch a:focus-visible,
    .sa-forgot:focus-visible,
    .sa-eye:focus-visible {
        outline: 3px solid rgba(107, 0, 32, .35);
        outline-offset: 2px;
    }

    /* sliding panel */
    .sa-panel {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 2;
        width: 50%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 32px 44px;
        overflow: hidden;
        color: #fff;
        /* maroon fade over the gown photo so the white text stays readable */
        background:
            linear-gradient(180deg, rgba(79, 0, 24, .62) 0%, rgba(79, 0, 24, .72) 55%, rgba(79, 0, 24, .92) 100%),
            url("{{ asset('images/hero-gown.jpg') }}") center / cover no-repeat,
            #5e0f27;
        transition: transform .7s cubic-bezier(.65, 0, .25, 1);
    }

    .sa-card[data-mode="register"] .sa-panel {
        transform: translateX(100%);
    }

    .sa-brand,
    .sa-copies,
    .sa-foot {
        position: relative;
        z-index: 1;
    }

    .sa-brand {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .sa-brand img {
        border-radius: 14px;
        background: #fff;
        padding: 4px;
    }

    .sa-brand span {
        padding-left: 16px;
        border-left: 1px solid rgba(255, 255, 255, .3);
        color: rgba(255, 255, 255, .85);
        font-size: 9px;
        letter-spacing: .25em;
        text-transform: uppercase;
    }

    .sa-copies {
        display: grid;
        max-width: 400px;
    }

    .sa-copy {
        grid-area: 1 / 1;
        opacity: 0;
        pointer-events: none;
        transition: opacity .35s ease;
    }

    .sa-card[data-mode="login"] .sa-copy-login,
    .sa-card[data-mode="register"] .sa-copy-register {
        opacity: 1;
        pointer-events: auto;
        transition-delay: .3s;
    }

    .sa-eyebrow {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
        color: #f0d79a;
        font-size: 12px;
        letter-spacing: .3em;
        text-transform: uppercase;
    }

    .sa-eyebrow u {
        width: 40px;
        height: 1px;
        background: #d9b86a;
    }

    .sa-copy h1 {
        margin: 0 0 14px;
        font: 400 40px/1.15 'Playfair Display', serif;
    }

    .sa-copy h1 span {
        display: block;
        color: #f0d79a;
        font-style: italic;
    }

    .sa-copy p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 15px;
        line-height: 1.7;
    }

    .sa-foot {
        color: rgba(255, 255, 255, .75);
        font-size: 12px;
        letter-spacing: .04em;
    }

    /* mobile: no slide, one form at a time under a short header */
    @media (max-width: 767px) {
        .sa-forms {
            display: block;
            min-height: 0;
        }

        .sa-panel {
            position: relative;
            width: auto;
            padding: 28px;
            transform: none !important;
            transition: none;
        }

        .sa-copies {
            margin: 28px 0;
        }

        .sa-copy {
            grid-area: auto;
            display: none;
            opacity: 1;
        }

        .sa-card[data-mode="login"] .sa-copy-login,
        .sa-card[data-mode="register"] .sa-copy-register {
            display: block;
        }

        .sa-copy h1 {
            font-size: 34px;
        }

        .sa-foot {
            display: none;
        }

        .sa-form-register .sa-fields {
            grid-template-columns: 1fr;
        }

        .sa-form {
            display: none;
            padding: 32px 24px;
            opacity: 1;
            visibility: visible;
        }

        .sa-card[data-mode="login"] .sa-form-login,
        .sa-card[data-mode="register"] .sa-form-register {
            display: flex;
        }

        .sa-card {
            display: flex;
            flex-direction: column;
        }

        .sa-card .sa-forms {
            order: 2;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .sa-panel,
        .sa-form,
        .sa-copy {
            transition: none !important;
        }
    }
</style>

<script>
    (() => {
        const card = document.getElementById('saCard');
        if (!card) return;

        const urls = { login: @json(route('login', [], false)), register: @json(route('register', [], false)) };
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const setMode = (mode, push) => {
            card.dataset.mode = mode;
            card.querySelectorAll('.sa-form').forEach(f => f.setAttribute('aria-hidden', f.dataset.form !== mode));
            if (push) history.pushState({ mode }, '', urls[mode]);

            setTimeout(() => {
                const field = card.querySelector('.sa-form-' + mode + ' input:not([type="hidden"])');
                if (field) field.focus({ preventScroll: true });
            }, reduce ? 0 : 500);
        };

        card.addEventListener('click', e => {
            const link = e.target.closest('[data-auth-switch]');
            if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
            e.preventDefault();
            setMode(link.dataset.authSwitch, true);
        });

        window.addEventListener('popstate', () => {
            setMode(location.pathname.endsWith('/register') ? 'register' : 'login', false);
        });

        card.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePassword);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    })();
</script>