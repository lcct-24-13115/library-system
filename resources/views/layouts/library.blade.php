<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SALRC Library')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">

    @php
        $link = fn ($active) => $active
            ? 'underline underline-offset-4 decoration-2 decoration-indigo-400'
            : 'hover:text-indigo-200';
    @endphp

    <nav class="bg-indigo-900 text-white px-6 py-4 flex flex-col md:flex-row md:justify-between md:items-center gap-3 shadow-md">
        <h1 class="text-xl font-bold tracking-wide">📚 SALRC Library System</h1>
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm font-medium">
            <a href="/dashboard" class="hover:text-indigo-200">Dashboard</a>
            <a href="{{ route('books.index') }}" class="{{ $link(request()->routeIs('books.*')) }}">Books Catalog</a>
            <a href="{{ route('patrons.index') }}" class="{{ $link(request()->routeIs('patrons.*')) }}">Patrons</a>
            <a href="{{ route('loans.create') }}" class="{{ $link(request()->routeIs('loans.create')) }}">Issue Book</a>
            <a href="{{ route('loans.return.form') }}" class="{{ $link(request()->routeIs('loans.return.form')) }}">Return Book</a>
            <a href="{{ route('loans.index') }}" class="{{ $link(request()->routeIs('loans.index')) }}">Loans</a>
        </div>
    </nav>

    <div class="@yield('width', 'max-w-6xl') mx-auto py-10 px-4">
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-100 border border-emerald-400 text-emerald-800 rounded-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-100 border border-rose-400 text-rose-700 rounded-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 bg-rose-100 border border-rose-400 text-rose-700 rounded-lg text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

</body>
</html>
