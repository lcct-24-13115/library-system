<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head')
        <title>{{ config('app.name', 'SALRC Library') }}</title>
    </head>
    <body class="salrc">
        <div class="landing">
            <nav class="landing-nav">
                <a href="{{ url('/') }}" class="landing-brand">
                    <img src="{{ asset('images/salrc-logo-full.png') }}" alt="Saint Augustine Learning Resource Center" style="width:56px;height:56px;object-fit:contain;flex-shrink:0;">
                    <div>
                        <strong>SALRC</strong>
                        <small>Library System</small>
                    </div>
                </a>
            </nav>

            <main class="guest-main">
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
