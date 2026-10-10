<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="login-password-public-key"
        content="{{ app(\App\Services\LoginPasswordEncryption::class)->publicKey() }}">

    <title>{{ config('app.name', 'Shyra Beautique') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:400,500,600,700&display=swap"
        rel="stylesheet"
    />

    @vite(['resources/css/app.css', 'resources/css/gold-palette.css', 'resources/js/app.js'])

    <style>
        .font-display {
            font-family: 'Playfair Display', serif;
        }

        .font-body {
            font-family: 'DM Sans', sans-serif;
        }

        .auth-decoration {
            pointer-events: none;
            user-select: none;
        }

        .auth-float {
            animation: authFloat 5s ease-in-out infinite;
        }

        .auth-float-slow {
            animation: authFloat 7s ease-in-out infinite;
        }

        @keyframes authFloat {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .luxury-button {
            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease,
                background-color 0.3s ease;
        }

        .luxury-button:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 25px rgba(92, 26, 43, 0.20);
        }

        .luxury-button:active {
            transform: translateY(-1px) scale(0.98);
        }
    </style>
</head>

<body class="font-body bg-[#F8EEF2] text-[#2E2A26] antialiased">

    {{ $slot }}

</body>

</html>