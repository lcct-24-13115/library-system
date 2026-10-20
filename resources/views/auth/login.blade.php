<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts._head')
    <title>Sign in — {{ config('app.name', 'SALRC Library') }}</title>
</head>

@php
    // Customize here — logo path and copy are all editable in one place.
    $logo        = asset('images/salrc-logo.svg'); // replace with images/salrc-logo.png if you have the PNG
    $description = 'One place to access all library resources, track loans, and manage your learning journey, from start to finish.';
    $features    = [
        'Access digital collections and research databases',
        'Request books and manage your loans online',
        'Track all research help and service tickets',
    ];
@endphp

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

    <div class="login-wrap">

        {{-- Left: what the system is for --}}
        <section class="login-hero">
            <div class="hero-kicker"><span class="kicker-line"></span>Staff portal</div>
            <h1 class="hero-title">Welcome<br><em>back to the</em><br>library desk.</h1>
            <p class="hero-copy" style="margin-bottom:0">{{ $description }}</p>

            <ul class="feature-list">
                @foreach ($features as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        </section>

        {{-- Right: sign-in card --}}
        <section class="auth-card" aria-labelledby="signin-title">
            <div class="auth-seal">
                <img src="{{ $logo }}" alt="Saint Augustine Learning Resource Center seal" width="54" height="54">
                <div>
                    <h2 id="signin-title">Sign in</h2>
                    <p class="sub">Use your school account.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="notice" role="status" style="margin-top:0;margin-bottom:18px">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" style="display:grid;gap:18px">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           placeholder="salrc@college.edu" required autofocus autocomplete="username">
                    @error('email')
                        <p class="err">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
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

                <button type="submit" class="btn btn-primary">Sign in</button>
            </form>

            @if (Route::has('password.request'))
                <p class="auth-links">
                    <a href="{{ route('password.request') }}">Forgot your password?</a>
                </p>
            @endif
        </section>
    </div>

    <footer class="landing-bottom">
        <span>SAINT AUGUSTINE LEARNING RESOURCE CENTER</span>
        <span>Staff portal · Secure access</span>
    </footer>
</div>

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
