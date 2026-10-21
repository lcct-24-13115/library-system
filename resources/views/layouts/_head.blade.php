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
                // --- 3D theme additions (shadows, keyframes, transform utilities) ---
                boxShadow: {
                    tactile: '0 8px 0 #1e293b, 0 16px 20px -6px rgba(15,23,42,.45)',
                    'tactile-blue': '0 8px 0 #1e3a8a, 0 16px 20px -6px rgba(29,78,216,.5)',
                    depth: '0 2px 4px rgba(15,37,87,.06), 0 12px 24px -8px rgba(15,37,87,.18), 0 30px 50px -24px rgba(15,37,87,.22)',
                    'depth-lg': '0 4px 8px rgba(15,37,87,.08), 0 22px 36px -10px rgba(15,37,87,.26), 0 44px 70px -26px rgba(29,78,216,.3)'
                },
                keyframes: {
                    floaty: { '0%,100%': { translate: '0 0' }, '50%': { translate: '0 -8px' } },
                    rise3d: { from: { opacity: 0, transform: 'translateY(26px) rotateX(-10deg)' }, to: { opacity: 1, transform: 'none' } }
                },
                animation: { floaty: 'floaty 6s ease-in-out infinite', rise3d: 'rise3d .6s cubic-bezier(.2,.8,.2,1) both' },
                colors: (function () {
                    // Blue & white scheme. The existing views use indigo / emerald / slate / gray
                    // utility classes, so re-mapping those scales recolours every page at once.
                    var cool = {
                        50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8',
                        500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a', 950: '#020617'
                    };
                    var blue = {
                        50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa',
                        500: '#2563eb', 600: '#1d4ed8', 700: '#1e40af', 800: '#1e3a8a', 900: '#16307a', 950: '#0b1b4a'
                    };
                    var sky = {
                        50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8',
                        500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e'
                    };
                    var rose = {
                        50: '#fcf1ee', 100: '#f9e6e1', 200: '#f1d0c9', 300: '#e5aaa0', 400: '#d4796c',
                        500: '#c4574b', 600: '#b3453d', 700: '#a33b35', 800: '#842f2a', 900: '#6b2723'
                    };
                    return {
                        slate: cool,
                        gray: cool,
                        indigo: blue,
                        emerald: sky,   // "available / returned / success" = light blue
                        green: sky,
                        red: rose,
                        rose: rose,     // errors and overdue stay red on purpose
                        amber: {
                            50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24',
                            500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f'
                        }
                    };
                })()
            }
        },
        plugins: [
            // 3D utilities: perspective-*, preserve-3d, rotate-x-*, rotate-y-*, translate-z-*
            function (api) {
                var u = {
                    '.preserve-3d': { 'transform-style': 'preserve-3d' },
                    '.backface-hidden': { 'backface-visibility': 'hidden' },
                    '.perspective-600': { perspective: '600px' },
                    '.perspective-1000': { perspective: '1000px' },
                    '.perspective-1500': { perspective: '1500px' }
                };
                [3, 6, 12, 45].forEach(function (d) {
                    u['.rotate-x-' + d] = { transform: 'rotateX(' + d + 'deg)' };
                    u['.rotate-y-' + d] = { transform: 'rotateY(' + d + 'deg)' };
                });
                [10, 20, 40].forEach(function (d) { u['.translate-z-' + d] = { transform: 'translateZ(' + d + 'px)' }; });
                api.addUtilities(u, { variants: ['hover', 'focus', 'active', 'group-hover'] });
            }
        ]
    };
</script>
<link rel="stylesheet" href="{{ asset('css/salrc.css') }}?v={{ file_exists(public_path('css/salrc.css')) ? filemtime(public_path('css/salrc.css')) : 1 }}">
<link rel="stylesheet" href="{{ asset('css/salrc-3d.css') }}?v={{ file_exists(public_path('css/salrc-3d.css')) ? filemtime(public_path('css/salrc-3d.css')) : 1 }}">
<script src="{{ asset('js/salrc-3d.js') }}?v={{ file_exists(public_path('js/salrc-3d.js')) ? filemtime(public_path('js/salrc-3d.js')) : 1 }}" defer></script>
