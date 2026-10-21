<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ config('app.name', 'SALRC Library') }}</title>

    {{-- The book starts closed and opens by itself. It stays open (no animation) when
         there are validation errors or the visitor prefers reduced motion. Without
         JavaScript this class is never set, so the form is simply visible. --}}
    <script>
        (function () {
            var hasErrors = {{ $errors->any() ? 'true' : 'false' }};
            var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!hasErrors && !reduce) document.documentElement.classList.add('book-closed');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}">

    {{-- Base blue & white stylesheet, then the book layer on top of it. --}}
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ file_exists(public_path('css/login.css')) ? filemtime(public_path('css/login.css')) : 1 }}">
    <link rel="stylesheet" href="{{ asset('css/login-book.css') }}?v={{ file_exists(public_path('css/login-book.css')) ? filemtime(public_path('css/login-book.css')) : 1 }}">
    <script src="{{ asset('js/login-book.js') }}?v={{ file_exists(public_path('js/login-book.js')) ? filemtime(public_path('js/login-book.js')) : 1 }}" defer></script>
</head>

@php
    // Customize here — logo and background are editable in one place.
    $logo = asset('images/salrc-logo-full.png');
    $portal = old('portal', request('portal'));
@endphp

<body class="login-page">
    {{-- Poster background: replace public/images/login-bg.jpg to change it --}}
    <div class="login-bg" aria-hidden="true"><span class="beam beam-a"></span><span class="beam beam-b"></span></div>

    <main class="login-stage">
        <div class="book-scene" id="book-scene">
            <div class="book-glow" aria-hidden="true"></div>
            <div class="book-ground" aria-hidden="true"></div>

            <div class="book-shift">
              <div class="book-rest">
                <div class="book-tilt" id="book-tilt">
                  <div class="book-float">
                    <div class="book">

                        {{-- structure: back board, spine, page edges, left page stack, loose leaves --}}
                        <div class="b-back"></div>
                        <div class="b-spine"></div>
                        <div class="b-edge b-edge--r"></div>
                        <div class="b-edge b-edge--t"></div>
                        <div class="b-edge b-edge--b"></div>
                        <div class="b-slab"></div>
                        <div class="b-leaf b-leaf--2"></div>
                        <div class="b-leaf b-leaf--1"></div>

                        {{-- RIGHT PAGE: the sign-in form --}}
                        <section class="b-page" id="book-page" aria-label="Sign in">
                            <div class="b-page__inner">

                                <button type="button" class="pg-close" id="book-close-btn" aria-label="Close the book">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H19v14H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H19v-3"/></svg>
                                    Close book
                                </button>

                                <div class="m-head" aria-hidden="true">
                                    <img src="{{ $logo }}" alt="" width="46" height="46">
                                    <div>
                                        <p class="m-head__college">La Consolacion College Tanauan</p>
                                        <p class="m-head__title">Welcome back</p>
                                    </div>
                                </div>

                                {{-- Desktop logo above the form (mobile uses .m-head instead) --}}
                                <div class="pg-logo">
                                    <img src="{{ asset('images/salrc-badge.png') }}" alt="Saint Augustine Learning Resource Center logo" width="96" height="96">
                                </div>

                                @if (session('status'))
                                    <div class="lf-notice" role="status">{{ session('status') }}</div>
                                @endif

                                <form method="POST" action="{{ route('login') }}" class="login-form" id="login-form" aria-labelledby="signin-title">
                                    @csrf

                                    {{-- Optional: choose the type of account you are signing in with.
                                         Only shown when App\Models\User::PORTALS exists. --}}
                                    @if (defined('App\\Models\\User::PORTALS'))
                                    <div class="lf-field">
                                        <span class="lf-label" id="portal-label">Sign in as</span>
                                        <div class="role-tabs" role="radiogroup" aria-labelledby="portal-label">
                                            @foreach(\App\Models\User::PORTALS as $key => $label)
                                                <label class="role-tab">
                                                    <input type="radio" name="portal" value="{{ $key }}" @checked($portal === $key)>
                                                    <span>{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <div class="lf-field">
                                        <label for="email">Email or ID number</label>
                                        <input id="email" name="email" type="text" value="{{ old('email') }}"
                                               placeholder="salrc@lcctanauan.edu.ph or ID number" required autocomplete="username">
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

                                    <button type="submit" class="lf-submit">Sign in</button>
                                </form>

                                <p class="login-links">
                                    <a href="{{ route('catalog.index') }}">Search the catalog without an account</a>
                                </p>

                                @if (Route::has('password.request'))
                                    <p class="login-links">
                                        <a href="{{ route('password.request') }}">Forgot your password?</a>
                                    </p>
                                @endif
                            </div>
                        </section>

                        {{-- FRONT COVER: front = cover art, back = LEFT PAGE once it swings open --}}
                        <div class="b-cover">
                            <div class="b-cover__front">
                                <span class="foil-frame" aria-hidden="true"></span>
                                <p class="cv-college">La Consolacion College Tanauan</p>
                                <div class="cv-seal" aria-hidden="true"><img src="{{ $logo }}" alt="" width="132" height="132"></div>
                                <p class="cv-title">Saint Augustine Learning Resource Center</p>
                                <p class="cv-hint" aria-hidden="true">Opening…</p>
                                <button type="button" class="cv-btn" id="book-cover-btn" aria-expanded="false" aria-controls="book-page" aria-label="Open the book to sign in"></button>
                            </div>

                            <div class="b-cover__back">
                                <div class="lp">
                                    <img class="lp__art" src="{{ asset('images/st-augustine.jpg') }}" alt="Saint Augustine of Hippo writing, holding a flaming heart (painting by Philippe de Champaigne)" width="720" height="919">
                                    <span class="lp__shade" aria-hidden="true"></span>
                                    <div class="lp__body">
                                        <p class="lp__college">La Consolacion College Tanauan</p>
                                        <h1 id="signin-title">Welcome back</h1>
                                        <p class="sub">Sign in to your library account.</p>
    <div class="lp__foot">
                                            <p>saintaugustinelearningresource@gmail.com</p>
                                            <p>A. Mabini Avenue, Tanauan City, Batangas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>
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
