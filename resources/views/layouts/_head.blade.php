<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

{{-- Tailwind via CDN (no asset build needed). The palette below re-maps the
     indigo / slate / gray / emerald / amber / rose scales that the existing
     views already use, so every page picks up the SALRC colours automatically. --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    display: ['Manrope', 'DM Sans', 'system-ui', 'sans-serif'],
                    mono: ['IBM Plex Mono', 'ui-monospace', 'monospace'],
                },
                colors: (function () {
                    var warm = {
                        50: '#f8f6ef', 100: '#f2eee3', 200: '#e4ded2', 300: '#d6cfc0', 400: '#8d9694',
                        500: '#66726f', 600: '#4f5d61', 700: '#3b4a52', 800: '#2b3d46', 900: '#1f2f3a', 950: '#16222b'
                    };
                    return {
                        slate: warm,
                        gray: warm,
                        indigo: {
                            50: '#eef4ef', 100: '#e1ede4', 200: '#c5dccb', 300: '#9cc2aa', 400: '#6fa389',
                            500: '#4d8a70', 600: '#28634f', 700: '#1e513f', 800: '#1b4335', 900: '#233643', 950: '#1a2832'
                        },
                        emerald: {
                            50: '#eff6f0', 100: '#e4f0e6', 200: '#c9e0cf', 300: '#9fc7ac', 400: '#6fa88a',
                            500: '#4d8a70', 600: '#2f7a5c', 700: '#286249', 800: '#214f3c', 900: '#1c3f31'
                        },
                        amber: {
                            50: '#fdf6ea', 100: '#f8edda', 200: '#f0d9b0', 300: '#e6c07f', 400: '#dda45c',
                            500: '#d79a52', 600: '#c0822f', 700: '#9a6521', 800: '#7d5019', 900: '#5f3d14'
                        },
                        rose: {
                            50: '#fcf1ee', 100: '#f9e6e1', 200: '#f1d0c9', 300: '#e5aaa0', 400: '#d4796c',
                            500: '#c4574b', 600: '#b3453d', 700: '#a33b35', 800: '#842f2a', 900: '#6b2723'
                        }
                    };
                })()
            }
        }
    };
</script>
<link rel="stylesheet" href="{{ asset('css/salrc.css') }}?v={{ file_exists(public_path('css/salrc.css')) ? filemtime(public_path('css/salrc.css')) : 1 }}">
