<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Browse and rent elegant gowns and formal wear at Shyra Beautique.">

    <title>Shyra Beautique | Gown Reservation & Rental</title>


    <!-- GOOGLE / BUNNY FONTS -->

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:400,500,600,700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/gold-palette.css', 'resources/js/app.js'])

    <style>
        /* ================================
       FONTS
    ================================= */

        .font-display {
            font-family: 'Playfair Display', serif;
        }

        .font-body {
            font-family: 'DM Sans', sans-serif;
        }


        /* ================================
       SMOOTH ANCHOR SCROLL
    ================================= */

        html {
            scroll-behavior: smooth;
        }


        /* ================================
       BODY
    ================================= */

        body {
            overflow-x: hidden;
        }

        /* Make the featured-card reservation links read as clear actions. */
        a.sb-home-reserve-button,
        a.sb-home-reserve-button:visited {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 38px !important;
            padding: 0 16px !important;
            border: 1px solid #64182d !important;
            border-radius: 999px !important;
            background: #64182d !important;
            color: #fffaf1 !important;
            font-family: 'DM Sans', sans-serif !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            box-shadow: 0 5px 12px rgba(94, 18, 41, .16) !important;
        }

        a.sb-home-reserve-button:hover {
            background: #7a2038 !important;
            border-color: #7a2038 !important;
            color: #fffaf1 !important;
            transform: translateY(-1px);
        }


        /* ================================
       SHADOWS
    ================================= */

        .luxury-shadow {
            box-shadow: 0 20px 50px rgba(92, 26, 43, 0.10);
        }

        .card-shadow {
            box-shadow: 0 10px 30px rgba(74, 20, 32, 0.08);
        }

        .soft-glow {
            box-shadow:
                0 0 0 1px rgba(201, 164, 106, 0.18),
                0 20px 45px rgba(92, 26, 43, 0.10);
        }


        /* ================================
       SECTION SCROLL ANIMATION
    ================================= */

        .scroll-reveal {
            opacity: 0;
            transform: translateY(70px);

            transition:
                opacity 0.7s ease-out,
                transform 0.7s ease-out;
        }

        .scroll-reveal.show {
            opacity: 1;
            transform: translateY(0);
        }


        /* ================================
       CARD SCROLL ANIMATION
    ================================= */

        .scroll-item {
            opacity: 0;
            transform: translateY(45px);

            transition:
                opacity 0.6s ease-out,
                transform 0.6s ease-out;
        }

        .scroll-item.show {
            opacity: 1;
            transform: translateY(0);
        }


        /* ================================
       CARD ANIMATION DELAY
    ================================= */

        .scroll-item:nth-child(1) {
            transition-delay: 0.05s;
        }

        .scroll-item:nth-child(2) {
            transition-delay: 0.15s;
        }

        .scroll-item:nth-child(3) {
            transition-delay: 0.25s;
        }

        .scroll-item:nth-child(4) {
            transition-delay: 0.35s;
        }


        /* ================================
       GOWN CARD HOVER
    ================================= */

        .gown-card {
            transition:
                transform 0.35s ease,
                box-shadow 0.35s ease;
        }

        .gown-card:hover {
            transform: translateY(-8px);

            box-shadow:
                0 20px 40px rgba(92, 26, 43, 0.15);
        }


        /* ================================
       GOWN IMAGE HOVER
    ================================= */

        .gown-image {
            transition: transform 0.5s ease;
        }

        .gown-card:hover .gown-image {
            transform: scale(1.05);
        }


        /* ================================
       MAIN BUTTON HOVER
    ================================= */

        .luxury-button {
            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease,
                background-color 0.3s ease;
        }

        .luxury-button:hover {
            transform: translateY(-3px) scale(1.04);

            box-shadow:
                0 12px 25px rgba(92, 26, 43, 0.20);
        }

        .luxury-button:active {
            transform: translateY(-1px) scale(0.98);
        }


        /* ================================
       EXTRA BUTTON HOVER
    ================================= */

        .hover-button {
            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease;
        }

        .hover-button:hover {
            transform: translateY(-3px) scale(1.04);

            box-shadow:
                0 12px 25px rgba(92, 26, 43, 0.18);
        }

        .hover-button:active {
            transform: translateY(-1px) scale(0.98);
        }


        /* ================================
       NAVIGATION LINK HOVER
    ================================= */

        nav a {
            transition:
                color 0.25s ease,
                transform 0.25s ease;
        }

        nav a:hover {
            transform: translateY(-1px);
        }


        /* ================================
       GENERAL INTERACTIVE TRANSITION
    ================================= */

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }
    </style>


</head>


<body class="font-body bg-[#F0E1BE] text-[#3A2C1A] antialiased">


    <!-- NAVBAR -->

    <header
        class="sb-home-nav fixed top-0 left-0 right-0 z-50 bg-[#F0E1BE]/95 backdrop-blur-md border-b border-[#C9A961]">

        <div class="max-w-7xl mx-auto px-6 lg:px-10">

            <div class="h-[78px] flex items-center justify-between">


                <!-- LOGO -->

                <a href="#home" class="flex items-center gap-4">

                    <img src="{{ asset('images/Logo.png') }}" alt="Shyra Beautique" class="sb-logo-lockup">

                    <div class="leading-tight border-l border-[#DCC79A] pl-4">

                        <div class="text-[10px] tracking-[0.2em] uppercase text-[#7A6440]">
                            Gown Rental
                        </div>

                    </div>

                </a>


                <!-- NAV LINKS -->

                <nav class="hidden lg:flex items-center gap-8">

                    <a href="#home" class="text-sm font-medium text-[#5E1229] hover:text-[#9A7633] transition">
                        Home
                    </a>

                    <a href="#collection" class="text-sm font-medium text-[#5E1229] hover:text-[#9A7633] transition">
                        Gowns
                    </a>

                    <a href="#how-it-works" class="text-sm font-medium text-[#5E1229] hover:text-[#9A7633] transition">
                        How It Works
                    </a>

                    <a href="#about" class="text-sm font-medium text-[#5E1229] hover:text-[#9A7633] transition">
                        About Us
                    </a>

                    <a href="#contact" class="text-sm font-medium text-[#5E1229] hover:text-[#9A7633] transition">
                        Contact
                    </a>

                </nav>


                <!-- RIGHT SIDE -->

                <div class="flex items-center gap-4">

                    <!-- SEARCH -->

                    <button
                        class="hidden sm:flex w-10 h-10 rounded-full items-center justify-center text-[#5E1229] hover:bg-[#E3CE9E] transition">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7">

                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />

                        </svg>

                    </button>


                    <!-- ACCOUNT -->

                    <a href="/login"
                        class="flex w-10 h-10 rounded-full items-center justify-center text-[#5E1229] bg-white hover:bg-[#E3CE9E] transition cursor-pointer">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7">

                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />

                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 15 0" />

                        </svg>

                    </a>


                    <!-- LOGIN -->

                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex luxury-button items-center justify-center px-6 py-3 rounded-full bg-[#5E1229] text-white text-sm font-semibold">

                        Login

                    </a>

                </div>

            </div>

        </div>

    </header>



    <!-- MAIN -->

    <main id="home" class="pt-[78px]">


        <!-- HERO -->

        <section class="relative min-h-[620px] lg:min-h-[680px] overflow-hidden bg-[#E6D2A2]">


            <!-- BACKGROUND IMAGE -->

            <!-- The gown sits in the right third of the artwork, so the image is
                 anchored to the right to keep it in frame at every width. -->

            <img src="{{ asset('images/background.png') }}" alt="Elegant gown collection"
                class="absolute inset-0 w-full h-full object-cover object-[70%_center]">


            <!-- OVERLAY -->

            <!-- A left-weighted scrim only. It keeps the headline legible on the
                 plain wall area and then clears completely across the gown, so the
                 red dress is not veiled at all. The previous stack of two full-bleed
                 cream washes was what desaturated it. -->

            <div class="absolute inset-0 bg-gradient-to-r from-[#F4E7CE]/95 via-[#F4E7CE]/55 via-55% to-[#F4E7CE]/0">
            </div>


            <!-- HERO CONTENT -->

            <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-10 min-h-[620px] lg:min-h-[680px] flex items-center">

                <div class="max-w-2xl py-20">


                    <div class="inline-flex items-center gap-2 mb-6">

                        <span class="w-10 h-px bg-[#9A7633]"></span>

                        <span class="text-xs tracking-[0.25em] uppercase text-[#9A7633] font-semibold">

                            Shyra Beautique

                        </span>

                    </div>


                    <h1
                        class="font-display text-5xl sm:text-6xl lg:text-[4.25rem] leading-[1.03] text-[#5E1229] font-semibold">

                        Elegant Gowns

                        <br>

                        <span class="font-normal italic text-[#9A7633]">
                            for Every Occasion
                        </span>

                    </h1>


                    <p class="mt-7 font-body text-[17px] leading-[1.75] text-[#4A3C26] max-w-xl">

                        Find a gown that makes you feel confident,
                        beautiful, and unforgettable. Discover elegant
                        formal wear for weddings, debuts, proms,
                        photoshoots, and other special occasions.

                    </p>


                    <div class="mt-9 flex flex-wrap gap-4">

                        <a href="#collection"
                            class="luxury-button inline-flex items-center justify-center px-7 py-3.5 rounded-full bg-[#5E1229] text-white font-semibold">

                            Explore Gowns

                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.7">

                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />

                            </svg>

                        </a>


                        <a href="{{ route('register') }}"
                            class="luxury-button inline-flex items-center justify-center px-7 py-3.5 rounded-full border border-[#9A7633] text-[#5E1229] font-semibold hover:bg-white/50 transition">

                            Create Account

                        </a>



                    </div>

                </div>

            </div>

        </section>



        <!-- BENEFITS -->

        <section class="scroll-reveal bg-[#E9D7AC] border-y border-[#C9A961]">

            <div class="max-w-7xl mx-auto px-6 lg:px-10">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">


                    <!-- BENEFIT 1 -->

                    <div class="py-10 px-6 text-center lg:border-r border-[#C9A961]">

                        <div
                            class="mx-auto mb-4 w-12 h-12 rounded-full bg-[#F0E1BE] flex items-center justify-center text-[#5E1229]">

                            ✦

                        </div>

                        <h3 class="font-display text-xl text-[#5E1229]">
                            Elegant Collection
                        </h3>

                        <p class="mt-2 text-sm text-[#6B5B45]">
                            Beautiful gowns for every special occasion.
                        </p>

                    </div>


                    <!-- BENEFIT 2 -->

                    <div class="py-10 px-6 text-center lg:border-r border-[#C9A961]">

                        <div
                            class="mx-auto mb-4 w-12 h-12 rounded-full bg-[#F0E1BE] flex items-center justify-center text-[#5E1229]">

                            ♡

                        </div>

                        <h3 class="font-display text-xl text-[#5E1229]">
                            Easy Reservation
                        </h3>

                        <p class="mt-2 text-sm text-[#6B5B45]">
                            Reserve your preferred gown online with ease.
                        </p>

                    </div>


                    <!-- BENEFIT 3 -->

                    <div class="py-10 px-6 text-center lg:border-r border-[#C9A961]">

                        <div
                            class="mx-auto mb-4 w-12 h-12 rounded-full bg-[#F0E1BE] flex items-center justify-center text-[#5E1229]">

                            ◷

                        </div>

                        <h3 class="font-display text-xl text-[#5E1229]">
                            Flexible Schedule
                        </h3>

                        <p class="mt-2 text-sm text-[#6B5B45]">
                            Check your reservation schedule anytime.
                        </p>

                    </div>


                    <!-- BENEFIT 4 -->

                    <div class="py-10 px-6 text-center">

                        <div
                            class="mx-auto mb-4 w-12 h-12 rounded-full bg-[#F0E1BE] flex items-center justify-center text-[#5E1229]">

                            ♡

                        </div>

                        <h3 class="font-display text-xl text-[#5E1229]">
                            Trusted Service
                        </h3>

                        <p class="mt-2 text-sm text-[#6B5B45]">
                            We help make your special event memorable.
                        </p>

                    </div>

                </div>

            </div>

        </section>



        <!-- FEATURED GOWNS -->

        <section id="collection" class="scroll-reveal bg-[#F0E1BE] py-24">

            <div class="max-w-7xl mx-auto px-6 lg:px-10">


                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-12">

                    <div>

                        <p class="text-xs tracking-[0.25em] uppercase text-[#9A7633] font-semibold mb-3">

                            Our Collection

                        </p>

                        <h2 class="font-display text-4xl sm:text-5xl text-[#5E1229]">

                            Featured Gowns

                        </h2>

                        <p class="mt-4 max-w-xl text-[#6B5B45] leading-7">

                            Explore some of our elegant gowns and formal wear
                            available for your next special occasion.

                        </p>

                    </div>


                    <a href="{{ route('register') }}"
                        class="text-sm font-semibold text-[#5E1229] hover:text-[#9A7633] transition">

                        View All Gowns →

                    </a>

                </div>



                <!-- GOWN CARDS -->

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7">


                    <!-- CARD 1 -->

                    <article class="scroll-item gown-card bg-white rounded-2xl overflow-hidden card-shadow">

                        <div class="relative h-[360px] overflow-hidden bg-[#E0CB9C]">

                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTwJM7S0DVfveufp0vEOGoQ19JSazLhHuSDybMWoS-P7Xs98bds71H_s7o&s=10"
                                alt="Champagne Dream gown" class="gown-image w-full h-full object-cover">

                            <span
                                class="absolute top-4 left-4 bg-white/90 text-[#5E1229] text-xs font-semibold px-3 py-1.5 rounded-full">

                                Featured

                            </span>

                        </div>


                        <div class="p-5">

                            <p class="text-xs uppercase tracking-wider text-[#7A6440]">

                                Formal Gown

                            </p>

                            <h3 class="font-display text-2xl text-[#5E1229] mt-1">

                                Champagne Dream

                            </h3>

                            <div class="flex items-center justify-between mt-5">

                                <span class="font-semibold text-[#5E1229]">

                                    ₱2,500

                                </span>

                                <a href="{{ route('register') }}" class="sb-home-reserve-button">

                                    Reserve Now

                                </a>

                            </div>

                        </div>

                    </article>



                    <!-- CARD 2 -->

                    <article class="scroll-item gown-card bg-white rounded-2xl overflow-hidden card-shadow">

                        <div class="relative h-[360px] overflow-hidden bg-[#E0CB9C]">

                            <img src="https://www.thewildflowershop.com/cdn/shop/files/Pamela_in_Red-13_620x.jpg?v=1742131736"
                                alt="Scarlet Elegance gown" class="gown-image w-full h-full object-cover">

                            <span
                                class="absolute top-4 left-4 bg-white/90 text-[#5E1229] text-xs font-semibold px-3 py-1.5 rounded-full">

                                Popular

                            </span>

                        </div>


                        <div class="p-5">

                            <p class="text-xs uppercase tracking-wider text-[#7A6440]">

                                Evening Gown

                            </p>

                            <h3 class="font-display text-2xl text-[#5E1229] mt-1">

                                Scarlet Elegance

                            </h3>

                            <div class="flex items-center justify-between mt-5">

                                <span class="font-semibold text-[#5E1229]">

                                    ₱2,800

                                </span>

                                <a href="{{ route('register') }}" class="sb-home-reserve-button">

                                    Reserve Now

                                </a>

                            </div>

                        </div>

                    </article>



                    <!-- CARD 3 -->

                    <article class="scroll-item gown-card bg-white rounded-2xl overflow-hidden card-shadow">

                        <div class="relative h-[360px] overflow-hidden bg-[#E0CB9C]">

                            <img src="https://theortensia.com/cdn/shop/files/Copy_of_Copy_of_New_Ecom_1_6.png?v=1770473673&width=1920"
                                alt="Rosé Bloom gown" class="gown-image w-full h-full object-cover">

                            <span
                                class="absolute top-4 left-4 bg-white/90 text-[#5E1229] text-xs font-semibold px-3 py-1.5 rounded-full">

                                New

                            </span>

                        </div>


                        <div class="p-5">

                            <p class="text-xs uppercase tracking-wider text-[#7A6440]">

                                Debut Gown

                            </p>

                            <h3 class="font-display text-2xl text-[#5E1229] mt-1">

                                Rosé Bloom

                            </h3>

                            <div class="flex items-center justify-between mt-5">

                                <span class="font-semibold text-[#5E1229]">

                                    ₱2,600

                                </span>

                                <a href="{{ route('register') }}" class="sb-home-reserve-button">

                                    Reserve Now

                                </a>

                            </div>

                        </div>

                    </article>



                    <!-- CARD 4 -->

                    <article class="scroll-item gown-card bg-white rounded-2xl overflow-hidden card-shadow">

                        <div class="relative h-[360px] overflow-hidden bg-[#E0CB9C]">

                            <img src="https://www.chicwish.com/media/catalog/product/cache/a87871bafb3bfd9c132042a6843aa84f/2/5/250821cc216.jpg"
                                alt="Midnight Grace gown" class="gown-image w-full h-full object-cover">

                            <span
                                class="absolute top-4 left-4 bg-white/90 text-[#5E1229] text-xs font-semibold px-3 py-1.5 rounded-full">

                                Elegant

                            </span>

                        </div>


                        <div class="p-5">

                            <p class="text-xs uppercase tracking-wider text-[#7A6440]">

                                Formal Gown

                            </p>

                            <h3 class="font-display text-2xl text-[#5E1229] mt-1">

                                Midnight Grace

                            </h3>

                            <div class="flex items-center justify-between mt-5">

                                <span class="font-semibold text-[#5E1229]">

                                    ₱2,700

                                </span>

                                <a href="{{ route('register') }}" class="sb-home-reserve-button">

                                    Reserve Now

                                </a>

                            </div>

                        </div>

                    </article>

                </div>

            </div>

        </section>



        <!-- HOW IT WORKS -->

        <section id="how-it-works" class="scroll-reveal bg-[#E3CE9E] py-24">

            <div class="max-w-7xl mx-auto px-6 lg:px-10">


                <div class="text-center max-w-2xl mx-auto mb-14">

                    <p class="text-xs tracking-[0.25em] uppercase text-[#9A7633] font-semibold mb-3">

                        Simple & Convenient

                    </p>

                    <h2 class="font-display text-4xl sm:text-5xl text-[#5E1229]">

                        How It Works

                    </h2>

                    <p class="mt-4 text-[#6B5B45] leading-7">

                        Reserving your dream gown is simple.
                        Follow these three easy steps.

                    </p>

                </div>



                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">


                    <!-- STEP 1 -->

                    <div class="bg-[#F0E1BE]/70 rounded-2xl p-8 text-center">

                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-[#5E1229] text-white flex items-center justify-center font-display text-2xl">

                            01

                        </div>

                        <h3 class="font-display text-2xl text-[#5E1229] mt-6">

                            Browse

                        </h3>

                        <p class="mt-3 text-[#6B5B45] leading-7">

                            Explore our available gowns and formal wear
                            and find the style that fits your occasion.

                        </p>

                    </div>



                    <!-- STEP 2 -->

                    <div class="bg-[#F0E1BE]/70 rounded-2xl p-8 text-center">

                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-[#5E1229] text-white flex items-center justify-center font-display text-2xl">

                            02

                        </div>

                        <h3 class="font-display text-2xl text-[#5E1229] mt-6">

                            Reserve

                        </h3>

                        <p class="mt-3 text-[#6B5B45] leading-7">

                            Select your preferred gown, choose your schedule,
                            and submit your reservation request.

                        </p>

                    </div>



                    <!-- STEP 3 -->

                    <div class="bg-[#F0E1BE]/70 rounded-2xl p-8 text-center">

                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-[#5E1229] text-white flex items-center justify-center font-display text-2xl">

                            03

                        </div>

                        <h3 class="font-display text-2xl text-[#5E1229] mt-6">

                            Enjoy

                        </h3>

                        <p class="mt-3 text-[#6B5B45] leading-7">

                            Get your reservation confirmed and enjoy
                            your special event in your chosen gown.

                        </p>

                    </div>

                </div>

            </div>

        </section>



        <!-- ABOUT US -->

        <section id="about" class="scroll-reveal bg-[#F0E1BE] py-24">

            <div class="max-w-7xl mx-auto px-6 lg:px-10">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">


                    <div>

                        <p class="text-xs tracking-[0.25em] uppercase text-[#9A7633] font-semibold mb-3">

                            About Shyra Beautique

                        </p>

                        <h2 class="font-display text-4xl sm:text-5xl text-[#5E1229] leading-tight">

                            Making Every Occasion

                            <span class="italic font-normal">
                                Beautiful
                            </span>

                        </h2>

                        <p class="mt-6 text-[#6B5B45] leading-8">

                            Shyra Beautique is a formal wear rental business
                            located in Bankerohan, Barangay 5-A, Poblacion District,
                            Davao City. We offer elegant gowns, dresses, suits,
                            barongs, and coordinated outfits for different
                            special occasions.

                        </p>

                        <p class="mt-4 text-[#6B5B45] leading-8">

                            Our goal is to make the gown rental experience
                            easier and more convenient by providing customers
                            with a simple way to browse available items,
                            check schedules, and make reservations online.

                        </p>

                        <a href="{{ route('register') }}"
                            class="inline-flex mt-8 items-center justify-center px-7 py-3.5 rounded-full bg-[#5E1229] text-white font-semibold luxury-button">

                            Start Your Reservation

                        </a>

                    </div>


                    <div>

                        <div class="rounded-3xl overflow-hidden soft-glow bg-[#E0CB9C]">

                            <img src="{{ asset('images/image.webp') }}" alt="Shyra Beautique formal wear"
                                class="w-full h-[500px] object-cover">

                        </div>

                    </div>

                </div>

            </div>

        </section>



        <!-- CTA -->

        <section id="contact" class="bg-[#5E1229] py-20">

            <div class="max-w-5xl mx-auto px-6 text-center">

                <p class="text-xs tracking-[0.25em] uppercase text-[#E0CB9C] font-semibold mb-4">

                    Your Special Occasion Awaits

                </p>

                <h2 class="font-display text-4xl sm:text-5xl text-white">

                    Find the Gown That

                    <span class="italic font-normal">
                        Feels Like You
                    </span>

                </h2>

                <p class="mt-5 max-w-2xl mx-auto text-[#E0CB9C] leading-7">

                    Browse our collection and reserve your favorite
                    gown for your next unforgettable event.

                </p>

                <div class="mt-8">

                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-[#F0E1BE] text-[#5E1229] font-semibold luxury-button">

                        Create Your Account

                    </a>

                </div>

            </div>

        </section>

    </main>



    <!-- FOOTER -->

    <footer class="bg-[#450E20] text-[#E0CB9C]">

        <div class="max-w-7xl mx-auto px-6 lg:px-10 py-14">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">


                <div>

                    <div class="font-display text-2xl text-white">

                        Shyra Beautique

                    </div>

                    <p class="mt-4 text-sm leading-7 text-[#C9A961] max-w-sm">

                        Elegant gowns and formal wear for
                        your most memorable occasions.

                    </p>

                </div>


                <div>

                    <h3 class="text-white font-semibold mb-4">

                        Quick Links

                    </h3>

                    <div class="space-y-3 text-sm">

                        <a href="#home" class="block hover:text-white transition">
                            Home
                        </a>

                        <a href="#collection" class="block hover:text-white transition">
                            Gowns
                        </a>

                        <a href="#how-it-works" class="block hover:text-white transition">
                            How It Works
                        </a>

                        <a href="#about" class="block hover:text-white transition">
                            About Us
                        </a>

                    </div>

                </div>


                <div>

                    <h3 class="text-white font-semibold mb-4">

                        Account

                    </h3>

                    <div class="space-y-3 text-sm">

                        <a href="{{ route('login') }}" class="block hover:text-white transition">

                            Login

                        </a>

                        <a href="{{ route('register') }}" class="block hover:text-white transition">

                            Create Account

                        </a>

                    </div>

                </div>

            </div>


            <div class="mt-12 pt-6 border-t border-white/10 text-center text-xs text-[#B08D48]">

                © {{ date('Y') }} Shyra Beautique.
                All rights reserved.

            </div>

        </div>

    </footer>


    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const elements = document.querySelectorAll(
                '.scroll-reveal, .scroll-item'
            );


            const observer = new IntersectionObserver(

                function (entries) {

                    entries.forEach(function (entry) {

                        if (entry.isIntersecting) {

                            entry.target.classList.add('show');

                        } else {

                            entry.target.classList.remove('show');

                        }

                    });

                },

                {
                    threshold: 0.15
                }

            );


            elements.forEach(function (element) {

                observer.observe(element);

            });

        });

    </script>


</body>

</html>
