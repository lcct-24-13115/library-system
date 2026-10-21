<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
    <title>@yield('title', 'SALRC Library')</title>
</head>
<body class="salrc-portal bg-slate-50 text-slate-800 min-h-screen">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/salrc-logo-full.png') }}" alt="" class="w-10 h-10 object-contain">
                <span class="leading-tight">
                    <span class="block font-extrabold text-slate-900">SALRC Library</span>
                    <span class="block text-xs text-slate-500">Saint Augustine Learning Resource Center</span>
                </span>
            </a>
            <nav class="flex items-center gap-4 text-sm font-semibold">
                <a href="{{ route('catalog.index') }}" class="text-indigo-700 hover:text-indigo-900">Catalog</a>
                @auth
                    @if(auth()->user()->isPatron())
                        <a href="{{ route('portal.index') }}" class="text-indigo-700 hover:text-indigo-900">My Library</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="text-indigo-700 hover:text-indigo-900">Staff dashboard</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-slate-600 hover:text-slate-900">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg hover:bg-indigo-700">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="text-center text-xs text-slate-400 pb-8">Saint Augustine Learning Resource Center</footer>
    @include('partials.chatbot')
</body>
</html>
