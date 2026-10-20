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
                    <div class="brand-mark"><x-icon name="library" :size="20" /></div>
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
