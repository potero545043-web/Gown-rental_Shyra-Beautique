﻿<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-5 md:p-8 relative overflow-hidden">
        <div class="absolute inset-0 overflow-hidden auth-decoration">
            <div
                class="absolute -left-10 bottom-0 text-[22rem] leading-none font-display font-bold text-[#5C1A2B]/[0.025]">
                S</div>
            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full border border-[#B76E79]/20"></div>
            <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full border border-[#B76E79]/15"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full border border-[#B76E79]/20"></div>
            <div class="auth-float absolute top-24 left-[12%] text-[#B76E79]/50 text-2xl">✦</div>
            <div class="auth-float-slow absolute top-[35%] right-[8%] text-[#C9A46A]/60 text-xl">✧</div>
        </div>

        <div
            class="relative z-10 w-full max-w-5xl bg-white rounded-[2rem] overflow-hidden shadow-[0_25px_70px_rgba(92,26,43,0.12)] grid grid-cols-1 md:grid-cols-2">
            <div
                class="relative overflow-hidden bg-[#5C1A2B] text-white min-h-[320px] md:min-h-[600px] flex flex-col justify-between p-8 md:p-12">
                <div class="absolute inset-0 auth-decoration">
                    <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/[0.04]"></div>
                    <div class="absolute -bottom-32 -left-20 w-80 h-80 rounded-full border border-white/[0.08]"></div>
                    <div class="absolute top-1/2 -right-16 w-40 h-40 rounded-full border border-white/[0.06]"></div>
                    <div
                        class="absolute -bottom-16 -right-4 text-[18rem] leading-none font-display font-bold text-white/[0.035]">
                        S</div>
                </div>

                <div class="relative z-10 flex items-center gap-4">
                    <img src="{{ asset('images/Logo.png') }}" alt="Shyra Beautique" class="sb-logo-lockup">
                    <div class="border-l border-white/25 pl-4">
                        <div class="text-[9px] tracking-[0.25em] uppercase text-white/60">Gown Reservation &amp; Rental
                        </div>
                    </div>
                </div>

                <div class="relative z-10 max-w-md py-10 md:py-0">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="h-px w-10 bg-[#C9A46A]"></span>
                        <span class="text-xs tracking-[0.3em] uppercase text-[#E5CFA5]">Join Us</span>
                        <span class="text-[#C9A46A]">✦</span>
                    </div>
                    <h1 class="font-display text-4xl md:text-5xl leading-tight mb-5">
                        Your next look
                        <span class="italic text-[#E5CFA5]">starts here.</span>
                    </h1>
                    <p class="text-white/70 leading-relaxed text-sm md:text-base">
                        Create an account to browse gowns, request reservation dates, and keep track of your bookings.
                    </p>
                </div>

                <div class="relative z-10 text-xs text-white/40 tracking-wide">Elegant looks. Easy reservations.</div>
            </div>

            <div class="bg-white p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-7">
                    <span
                        class="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.2em] uppercase text-[#B76E79]">
                        <span class="text-[#C9A46A]">✦</span>
                        New Customer Account
                    </span>
                    <h2 class="font-display text-4xl text-[#2E2A26] mt-3">Create an account</h2>
                    <p class="text-sm text-[#6F6662] mt-2">Enter your details to get started.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-[#5C1A2B] mb-2">Your name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            autocomplete="name" placeholder="Enter your full name"
                            class="w-full rounded-xl border border-[#E8D7DD] bg-[#FCF8FA] px-4 py-3 text-sm text-[#2E2A26] placeholder-[#A99CA0] outline-none transition focus:border-[#B76E79] focus:ring-2 focus:ring-[#B76E79]/10">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-[#5C1A2B] mb-2">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            autocomplete="username" placeholder="you@example.com"
                            class="w-full rounded-xl border border-[#E8D7DD] bg-[#FCF8FA] px-4 py-3 text-sm text-[#2E2A26] placeholder-[#A99CA0] outline-none transition focus:border-[#B76E79] focus:ring-2 focus:ring-[#B76E79]/10">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-[#5C1A2B] mb-2">Create a
                            password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            placeholder="At least 8 characters"
                            class="w-full rounded-xl border border-[#E8D7DD] bg-[#FCF8FA] px-4 py-3 text-sm text-[#2E2A26] placeholder-[#A99CA0] outline-none transition focus:border-[#B76E79] focus:ring-2 focus:ring-[#B76E79]/10">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-semibold text-[#5C1A2B] mb-2">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            autocomplete="new-password" placeholder="Enter your password again"
                            class="w-full rounded-xl border border-[#E8D7DD] bg-[#FCF8FA] px-4 py-3 text-sm text-[#2E2A26] placeholder-[#A99CA0] outline-none transition focus:border-[#B76E79] focus:ring-2 focus:ring-[#B76E79]/10">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <button type="submit"
                        class="luxury-button w-full flex items-center justify-center rounded-xl bg-[#5C1A2B] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#5C1A2B]/10">
                        Create my account
                    </button>
                </form>

                <div class="text-center mt-6 text-sm text-[#6F6662]">
                    Already have an account?
                    <a href="{{ route('login') }}"
                        class="font-semibold text-[#5C1A2B] hover:text-[#B76E79] transition">Log In</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>