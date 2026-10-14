<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ config('app.name', 'SALRC Library') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Same Tailwind CDN setup as layouts/library.blade.php, so no asset build is needed --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        cobalt: '#1d4ed8',
                        navy: { 900: '#0a1433', 950: '#060c24' },
                    },
                },
            },
        };
    </script>
    <style>
        .bg-dots {
            background-image: radial-gradient(rgba(255,255,255,.18) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .bg-grid {
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 64px 64px;
        }
        @media (prefers-reduced-motion: reduce) {
            .transform-gpu, .transform-gpu * { transition: none !important; }
        }
    </style>
</head>

@php
    // Customize here — logo path, title and copy are all editable in one place.
    $logo        = asset('images/salrc-logo.svg'); // replace with images/salrc-logo.png if you have the PNG
    $title       = 'SALRC Library LRC';
    $description = 'One place to access all library resources, track loans, and manage your learning journey, from start to finish.';
    $features    = [
        'Access digital collections and research databases',
        'Request books and manage your loans online',
        'Track all research help and service tickets',
    ];
@endphp

<body class="font-sans antialiased bg-navy-950 text-white">
<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- ============ LEFT PANEL (60%) ============ --}}
    <section class="relative isolate overflow-hidden lg:w-3/5 flex items-center justify-center px-6 py-12 sm:px-12 lg:px-16
                    bg-gradient-to-br from-cobalt via-blue-800 to-navy-900">

        {{-- background patterns & depth --}}
        <div class="absolute inset-0 -z-10 bg-dots opacity-60" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-grid" aria-hidden="true"></div>
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-sky-400/30 blur-3xl -z-10" aria-hidden="true"></div>
        <div class="absolute top-1/3 -right-24 h-80 w-80 rounded-full bg-indigo-500/30 blur-3xl -z-10" aria-hidden="true"></div>
        <div class="absolute -bottom-40 left-1/4 h-[28rem] w-[28rem] rounded-full bg-blue-950/70 blur-3xl -z-10" aria-hidden="true"></div>
        <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full border border-white/10 -z-10" aria-hidden="true"></div>
        <div class="absolute -bottom-12 -right-12 h-48 w-48 rounded-full border border-white/10 -z-10" aria-hidden="true"></div>

        <div class="w-full max-w-2xl flex flex-col items-center text-center">

            {{-- Logo --}}
            <img src="{{ $logo }}"
                 alt="Saint Augustine Learning Resource Center seal"
                 width="128" height="128"
                 class="h-28 w-28 sm:h-32 sm:w-32 rounded-full object-contain bg-white ring-4 ring-white/25 shadow-2xl shadow-blue-950/60">

            {{-- Title --}}
            <h1 class="mt-8 text-5xl sm:text-6xl xl:text-7xl font-extrabold tracking-tight leading-[1.05] text-white">
                {{ $title }}
            </h1>

            <p class="mt-6 max-w-xl text-base sm:text-lg leading-relaxed text-blue-100/90">
                {{ $description }}
            </p>

            {{-- Feature cards --}}
            <ul class="mt-10 w-full space-y-4 text-left">
                @foreach ($features as $feature)
                    <li class="transform-gpu flex items-center gap-4 rounded-2xl border border-white/15 bg-navy-950/40 backdrop-blur-md
                               px-5 py-4 shadow-xl shadow-black/25 transition duration-300 ease-out
                               hover:-translate-y-0.5 hover:border-white/30 hover:bg-navy-950/50">
                        <span class="h-3 w-3 shrink-0 rounded-full bg-orange-400 shadow-[0_0_12px_rgba(251,146,60,.8)]" aria-hidden="true"></span>
                        <span class="text-base sm:text-lg font-medium text-white/95">{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============ RIGHT PANEL (40%) ============ --}}
    <section class="relative isolate overflow-hidden lg:w-2/5 flex items-center justify-center px-6 py-12 sm:px-12 bg-navy-950">

        <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-cobalt/25 blur-3xl -z-10" aria-hidden="true"></div>
        <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-blue-700/20 blur-3xl -z-10" aria-hidden="true"></div>

        <div class="transform-gpu w-full max-w-md rounded-3xl border border-white/10 bg-white/5 backdrop-blur-md p-8 sm:p-10
                    shadow-2xl shadow-black/50">

            <h2 class="text-3xl font-bold text-white">Welcome</h2>
            <p class="mt-2 text-sm text-slate-300">Sign in with your school account.</p>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-200">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           placeholder="salrc@college.edu" required autofocus autocomplete="username"
                           class="mt-2 block w-full rounded-xl border border-white/10 bg-navy-950/70 px-4 py-3 text-white placeholder-slate-500
                                  shadow-inner focus:border-blue-400 focus:ring-2 focus:ring-blue-500/50 focus:outline-none transition">
                    @error('email')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-200">Password</label>
                    <div class="relative mt-2">
                        <input id="password" name="password" type="password"
                               placeholder="Enter your password" required autocomplete="current-password"
                               class="block w-full rounded-xl border border-white/10 bg-navy-950/70 py-3 pl-4 pr-20 text-white placeholder-slate-500
                                      shadow-inner focus:border-blue-400 focus:ring-2 focus:ring-blue-500/50 focus:outline-none transition">
                        <button type="button" id="togglePassword" aria-controls="password" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-4 text-sm font-semibold text-blue-300 hover:text-white
                                       focus:outline-none focus-visible:text-white transition">
                            Show
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="transform-gpu w-full rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white
                               shadow-lg shadow-blue-900/50 transition duration-200 ease-out
                               hover:bg-blue-500 hover:-translate-y-0.5 active:translate-y-0
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 focus-visible:ring-offset-2 focus-visible:ring-offset-navy-950">
                    Sign in
                </button>

                @if (Route::has('password.request'))
                    <p class="text-center">
                        <a href="{{ route('password.request') }}"
                           class="text-sm text-blue-300 hover:text-white underline-offset-4 hover:underline transition">
                            Forgot your password?
                        </a>
                    </p>
                @endif
            </form>
        </div>
    </section>
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
