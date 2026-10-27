<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SALRC Library') }}</title>

    {{-- Already seen in this browser tab? Go straight to the sign-in page (no flash). --}}
    <script>
        try {
            if (sessionStorage.getItem('salrc.splash') === '1') {
                document.documentElement.style.display = 'none';
                location.replace(@json(route('login')));
            }
        } catch (e) {}
    </script>
    <noscript><meta http-equiv="refresh" content="0;url={{ route('login') }}"></noscript>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}">

    <style>
        :root {
            --navy: #0a1433; --blue: #1e3a8a; --gold: #d9b45a; --paper: #f7f3ea;
            --w: min(300px, 44vw, 42vh);           /* width of ONE page */
            --h: calc(var(--w) * 1.33);
            --ease: cubic-bezier(.6, .05, .25, 1);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--navy);
            color: #fff;
            overflow: hidden;
            cursor: pointer;
            transition: opacity .5s ease, transform .5s ease;
        }
        body.leaving { opacity: 0; transform: scale(1.03); }

        .bg {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(60% 55% at 50% 52%, rgba(59, 130, 246, .28), rgba(10, 20, 51, 0) 70%),
                linear-gradient(180deg, rgba(10, 20, 51, .78), rgba(10, 20, 51, .9)),
                url('{{ asset('images/login-bg.jpg') }}') center / cover no-repeat;
            background-color: var(--navy);
        }

        .scene {
            position: relative; z-index: 1; height: 100%;
            perspective: 1700px; perspective-origin: 50% 45%;
        }

        /* hinge sits in the middle of the screen once the book is open */
        .shift {
            position: absolute; left: 50%; top: 46%; width: 0; height: 0;
            font-size: calc(var(--w) / 20);
            transform: translateX(calc(var(--w) / -2));
            animation: shift 1.7s var(--ease) 1s forwards;
        }
        .tilt {
            position: absolute; inset: 0; transform-style: preserve-3d;
            transform: rotateX(10deg) rotateY(-24deg) scale(.82);
            opacity: 0;
            animation: tilt-in .9s ease-out .1s forwards, tilt-open 1.7s var(--ease) 1s forwards;
        }
        .glow {
            position: absolute; left: calc(var(--w) * -1.1); top: calc(var(--h) * -.55);
            width: calc(var(--w) * 2.2); height: calc(var(--h) * 1.1);
            background: radial-gradient(closest-side, rgba(147, 197, 253, .35), rgba(147, 197, 253, 0));
            transform: translateZ(-40px); opacity: 0; animation: fade 1.4s ease 1.2s forwards;
        }

        .part { position: absolute; top: calc(var(--h) / -2); height: var(--h); }

        /* boards (navy hard cover) behind the pages */
        .board-r {
            left: -.4em; width: calc(var(--w) + .8em); top: calc(var(--h) / -2 - .4em); height: calc(var(--h) + .8em);
            background: linear-gradient(90deg, #0b1b4d, #14307a); border-radius: .3em .9em .9em .3em;
            transform: translateZ(-.5em); box-shadow: 0 2.2em 3.4em rgba(0, 0, 0, .55);
        }
        .board-l {
            left: calc(var(--w) * -1 - .4em); width: calc(var(--w) + .8em); top: calc(var(--h) / -2 - .4em); height: calc(var(--h) + .8em);
            background: linear-gradient(270deg, #0b1b4d, #14307a); border-radius: .9em .3em .3em .9em;
            transform: translateZ(-.5em); opacity: 0; animation: fade .3s ease 1.7s forwards;
        }

        /* right page: the welcome text */
        .right {
            left: 0; width: var(--w);
            background: linear-gradient(90deg, #e3dccb 0, var(--paper) 7%, #fffdf7 100%);
            border-radius: .15em .6em .6em .15em;
            box-shadow: .1em .1em 0 #e8e2d2, .2em .2em 0 #ddd5c1, .3em .3em 0 #e8e2d2, .4em .4em 0 #d3cab4;
            color: #0f172a; display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 2.4em 1.6em;
        }
        .right .kicker { font-size: .62em; letter-spacing: .22em; text-transform: uppercase; color: #1e3a8a; font-weight: 700; opacity: 0; animation: fade .7s ease 2.5s forwards; }
        .right h1 {
            font-family: 'Cormorant Garamond', Georgia, serif; font-size: 2.5em; line-height: 1.05; margin: .35em 0 .25em;
            color: #0a1433; opacity: 0; animation: rise .8s ease 2.6s forwards;
        }
        .right p { margin: 0; font-size: .8em; color: #475569; opacity: 0; animation: fade .8s ease 2.8s forwards; }
        .bar { margin-top: 1.6em; width: 62%; height: .32em; border-radius: 1em; background: #dbe4f5; overflow: hidden; opacity: 0; animation: fade .4s ease 2.7s forwards; }
        .bar i { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #1d4ed8, #60a5fa); animation: load 1.5s ease-in-out 2.7s forwards; }

        /* the cover swings open around the spine */
        .cover { left: 0; width: var(--w); transform-origin: left center; transform-style: preserve-3d; animation: open 1.7s var(--ease) 1s forwards; z-index: 3; }
        .face { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; overflow: hidden; }
        .front {
            background: radial-gradient(120% 90% at 30% 15%, #2a4fb0, #14307a 55%, #0b1b4d);
            border-radius: .15em .9em .9em .15em;
            box-shadow: inset 0 0 0 .1em rgba(255, 255, 255, .06), inset .9em 0 1.2em rgba(0, 0, 0, .35);
            display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2.2em 1.4em;
        }
        .front::before { content: ""; position: absolute; inset: 1em; border: .08em solid rgba(217, 180, 90, .8); border-radius: .5em; }
        .front .college { font-size: .6em; letter-spacing: .24em; text-transform: uppercase; color: var(--gold); font-weight: 700; margin: 0 0 1.6em; }
        .front img { width: 46%; height: auto; filter: drop-shadow(0 .4em .8em rgba(0, 0, 0, .45)); }
        .front .title { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 1.45em; line-height: 1.15; margin: 1.1em 0 0; color: #fff; }
        .back { transform: rotateY(180deg); background: linear-gradient(270deg, #e3dccb 0, var(--paper) 7%, #fffdf7 100%); border-radius: .6em .15em .15em .6em; }
        .back img { width: 100%; height: 72%; object-fit: cover; object-position: 50% 12%; display: block; }
        .back .cap { padding: .9em 1.1em; text-align: center; color: #1e3a8a; font-size: .62em; letter-spacing: .2em; text-transform: uppercase; font-weight: 700; }

        .foot { position: fixed; z-index: 2; left: 0; right: 0; bottom: 1.4em; text-align: center; font-size: .72rem; letter-spacing: .18em; text-transform: uppercase; color: rgba(255, 255, 255, .6); }
        .skip { position: fixed; z-index: 2; right: 1.4em; bottom: 1.2em; font-size: .78rem; color: rgba(255, 255, 255, .7); text-decoration: none; padding: .45em .9em; border: 1px solid rgba(255, 255, 255, .25); border-radius: 2em; }
        .skip:hover { background: rgba(255, 255, 255, .12); }

        @keyframes tilt-in   { to { opacity: 1; transform: rotateX(10deg) rotateY(-24deg) scale(1); } }
        @keyframes tilt-open { from { opacity: 1; transform: rotateX(10deg) rotateY(-24deg) scale(1); } to { opacity: 1; transform: rotateX(0) rotateY(0) scale(1); } }
        @keyframes shift     { to { transform: translateX(0); } }
        @keyframes open      { to { transform: rotateY(-180deg); } }
        @keyframes fade      { to { opacity: 1; } }
        @keyframes rise      { from { opacity: 0; transform: translateY(.5em); } to { opacity: 1; transform: none; } }
        @keyframes load      { to { width: 100%; } }

        /* Reduced motion: no swinging, just show the open book. */
        @media (prefers-reduced-motion: reduce) {
            .shift, .tilt, .cover, .glow, .board-l, .right .kicker, .right h1, .right p, .bar, .bar i { animation: none; }
            .shift { transform: none; }
            .tilt { opacity: 1; transform: none; }
            .cover { transform: rotateY(-180deg); }
            .glow, .board-l, .right .kicker, .right h1, .right p, .bar { opacity: 1; transform: none; }
            .bar i { width: 100%; }
        }
    </style>
</head>
<body id="splash" data-login="{{ route('login') }}">
    <div class="bg" aria-hidden="true"></div>

    <main class="scene" aria-label="Saint Augustine Learning Resource Center">
        <div class="shift">
            <div class="tilt">
                <div class="glow part" aria-hidden="true"></div>
                <div class="part board-l" aria-hidden="true"></div>
                <div class="part board-r" aria-hidden="true"></div>

                <section class="part right">
                    <span class="kicker">La Consolacion College Tanauan</span>
                    <h1>Welcome</h1>
                    <p>Saint Augustine Learning Resource Center</p>
                    <div class="bar" aria-hidden="true"><i></i></div>
                </section>

                <div class="part cover" aria-hidden="true">
                    <div class="face front">
                        <p class="college">La Consolacion College Tanauan</p>
                        <img src="{{ asset('images/salrc-logo-full.png') }}" alt="" width="132" height="132">
                        <p class="title">Saint Augustine Learning Resource Center</p>
                    </div>
                    <div class="face back">
                        <img src="{{ asset('images/st-augustine.jpg') }}" alt="" width="720" height="919">
                        <p class="cap">Learn. Lead. Love.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <p class="foot">Saint Augustine Learning Resource Center</p>
    <a class="skip" href="{{ route('login') }}" id="skip">Skip</a>

    <script>
        (function () {
            var body = document.body;
            var login = body.getAttribute('data-login');
            var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            var done = false, t1, t2;

            function go() {
                if (done) return;
                done = true;
                try {
                    sessionStorage.setItem('salrc.splash', '1');      // show the splash once per tab
                    sessionStorage.setItem('salrc.fromSplash', '1'); // the login book is already open
                } catch (e) {}
                location.replace(login);
            }

            function leave(delay) {
                clearTimeout(t1); clearTimeout(t2);
                body.classList.add('leaving');
                setTimeout(go, delay);
            }

            t1 = setTimeout(function () { body.classList.add('leaving'); }, reduce ? 1100 : 4500);
            t2 = setTimeout(go, reduce ? 1600 : 5000);

            // click, tap or any key skips
            body.addEventListener('click', function (e) { e.preventDefault(); leave(250); });
            document.addEventListener('keydown', function () { leave(250); });
        })();
    </script>
</body>
</html>
