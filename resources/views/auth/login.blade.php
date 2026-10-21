<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ config('app.name', 'SALRC Library') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}">

    {{-- Login has its own self-contained blue & white stylesheet. --}}
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ file_exists(public_path('css/login.css')) ? filemtime(public_path('css/login.css')) : 1 }}">
    {{-- 3D layer: floating glass card, tilt, Three.js particle grid + floating books. --}}
    <link rel="stylesheet" href="{{ asset('css/salrc-3d.css') }}?v={{ file_exists(public_path('css/salrc-3d.css')) ? filemtime(public_path('css/salrc-3d.css')) : 1 }}">
    <link rel="stylesheet" href="{{ asset('css/login-3d.css') }}?v={{ file_exists(public_path('css/login-3d.css')) ? filemtime(public_path('css/login-3d.css')) : 1 }}">
    <script src="{{ asset('js/salrc-3d.js') }}?v={{ file_exists(public_path('js/salrc-3d.js')) ? filemtime(public_path('js/salrc-3d.js')) : 1 }}" defer></script>
</head>

@php
    // Customize here — logo and background are editable in one place.
    $logo = asset('images/salrc-logo-full.png');
@endphp

<body class="login-page" data-bg3d="login">
    {{-- Poster background: replace public/images/login-bg.jpg to change it --}}
    <div class="login-bg" aria-hidden="true"></div>

    <main class="login-stage">
        <section class="login-card" aria-labelledby="signin-title" data-tilt="7">

            <div class="login-brand">
                <img src="{{ $logo }}" alt="Saint Augustine Learning Resource Center seal" width="58" height="58">
                <div>
                    <p class="college">La Consolacion College Tanauan</p>
                    <p class="center">Saint Augustine Learning Resource Center</p>
                </div>
            </div>

            <h1 id="signin-title">Welcome back</h1>
            <p class="sub">Sign in to your library account.</p>

            @if (session('status'))
                <div class="lf-notice" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf

                {{-- Optional: choose the type of account you are signing in with --}}
                @php $portal = old('portal', request('portal')); @endphp
                <div class="role-tabs" role="radiogroup" aria-label="Sign in as">
                    @foreach(\App\Models\User::PORTALS as $key => $label)
                        <label class="role-tab">
                            <input type="radio" name="portal" value="{{ $key }}" @checked($portal === $key)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="lf-field">
                    <label for="email">Email or ID number</label>
                    <input id="email" name="email" type="text" value="{{ old('email') }}"
                           placeholder="salrc@lcctanauan.edu.ph or ID number" required autofocus autocomplete="username">
                    @error('email')
                        <p class="err">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lf-field">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input id="password" name="password" type="password"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" id="togglePassword" class="pw-toggle" aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    @error('password')
                        <p class="err">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="lf-submit" data-tilt="10">Sign in</button>
            </form>

            <p class="login-links">
                <a href="{{ route('catalog.index') }}">Search the catalog without an account</a>
            </p>

            @if (Route::has('password.request'))
                <p class="login-links">
                    <a href="{{ route('password.request') }}">Forgot your password?</a>
                </p>
            @endif
        </section>
    </main>

    <footer class="login-foot">
        Saint Augustine Learning Resource Center · Secure access
    </footer>

<script>
    (function () {
        var btn = document.getElementById('togglePassword');
        var input = document.getElementById('password');
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-pressed', show);
        });
    })();
</script>
</body>
</html>
