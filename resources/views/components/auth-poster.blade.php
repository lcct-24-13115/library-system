@props(['title' => 'Account'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name', 'SALRC Library') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ file_exists(public_path('css/login.css')) ? filemtime(public_path('css/login.css')) : 1 }}">
</head>
<body class="login-page">
    <div class="login-bg" aria-hidden="true"></div>

    <main class="login-stage login-stage--center">
        <div class="login-card">
            <div class="login-brand">
                <img src="{{ asset('images/salrc-badge.png') }}" alt="" width="58" height="58">
                <div>
                    <p class="college">La Consolacion College Tanauan</p>
                    <p class="center">Saint Augustine Learning Resource Center</p>
                </div>
            </div>

            {{ $slot }}
        </div>
    </main>

    <footer class="login-foot">Saint Augustine Learning Resource Center · Secure access</footer>
</body>
</html>
