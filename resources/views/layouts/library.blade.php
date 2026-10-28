<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
    <title>@yield('title', 'SALRC Library')</title>
</head>
<body class="salrc">

    <div class="shell">
        @include('layouts._sidebar')

        <div class="main-column">
            @include('layouts._topbar')

            <main class="content print:p-0">
                <div class="@yield('width', 'max-w-7xl') mx-auto">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg shadow-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-rose-100 border border-rose-300 text-rose-700 rounded-lg shadow-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-rose-100 border border-rose-300 text-rose-700 rounded-lg text-sm">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @include('partials.web-search')

</body>
</html>
