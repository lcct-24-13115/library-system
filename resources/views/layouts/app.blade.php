<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head')
        <title>{{ config('app.name', 'SALRC Library') }}</title>
    </head>
    <body class="salrc">
        <div class="shell">
            @include('layouts._sidebar')

            <div class="main-column">
                @include('layouts._topbar')

                <main class="content">
                    <div class="max-w-5xl mx-auto">
                        @isset($header)
                            <div class="mb-6">
                                {{ $header }}
                            </div>
                        @endisset

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
