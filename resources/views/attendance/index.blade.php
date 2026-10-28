<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
    <title>Library Attendance - Saint Augustine Learning Center</title>
    <style>
        .att-bg {
            min-height: 100dvh;
            background:
                radial-gradient(900px 420px at 50% -120px, #dbe7ff 0%, rgba(219, 231, 255, 0) 70%),
                var(--cream);
        }
        .att-card { box-shadow: 0 2px 4px rgba(15,37,87,.06), 0 18px 40px -14px rgba(15,37,87,.22); }
        .att-photo { transition: opacity .25s ease, transform .25s ease; }
        .att-photo.is-hidden { opacity: 0; transform: translateY(6px); height: 0; margin: 0; overflow: hidden; }
        .att-stat-num { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="salrc">
<div class="att-bg flex flex-col">

    @if (auth()->check() && auth()->user()->hasRole('admin', 'librarian', 'staff'))
        {{-- Staff shortcuts (only shown when a librarian / staff is signed in in this browser) --}}
        <nav class="flex items-center justify-end gap-4 px-5 py-3 text-xs font-semibold text-slate-500 print:hidden">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-700">Dashboard</a>
            <a href="{{ route('attendance.stats') }}" class="hover:text-indigo-700">Statistics</a>
            <a href="{{ route('attendance.history') }}" class="hover:text-indigo-700">Visitor history</a>
        </nav>
    @else
        {{-- Public student view: the only way out is back to the sign-in page. --}}
        <nav class="flex items-center justify-start px-5 py-3 text-xs font-semibold text-slate-500 print:hidden">
            <a href="{{ route('login') }}" class="hover:text-indigo-700">&larr; Back to sign in</a>
        </nav>
    @endif

    <main class="flex-1 flex flex-col items-center px-4 pb-10">

        {{-- 1. Header --}}
        <header class="brand-hero brand-hero--center w-full max-w-3xl text-center mt-2 mb-8">
            <img src="{{ asset('images/salrc-logo-full.png') }}" alt="Saint Augustine Learning Center"
                 class="mx-auto w-20 h-20 object-contain mb-3">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Saint Augustine Learning Center</h1>
            <p class="mt-1 text-xs sm:text-sm font-bold tracking-[.28em] text-indigo-600 uppercase">Library Attendance</p>
        </header>

        {{-- 2. Form card --}}
        <section class="att-card w-full max-w-xl bg-white rounded-2xl border border-slate-200 p-6 sm:p-8">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Record your visit</h2>
            <p class="mt-2 text-sm text-slate-500">Enter your student ID — your photo will appear to confirm your identity, then tap Record entry.</p>

            <form id="att-form" action="{{ route('attendance.store') }}" method="POST" class="mt-6" novalidate>
                @csrf
                <label for="att-id" class="sr-only">Student ID</label>
                <input type="text" id="att-id" name="patron_id_number" autofocus autocomplete="off" autocapitalize="characters" spellcheck="false"
                       data-lookup-url="{{ route('attendance.lookup') }}"
                       placeholder="STUDENT ID (E.G. 24-13115)"
                       class="w-full rounded-xl border-2 border-slate-300 bg-white px-4 py-3.5 text-base font-semibold tracking-wide text-slate-900 placeholder:text-xs placeholder:font-bold placeholder:tracking-[.14em] placeholder:text-slate-400 focus:outline-none focus:border-indigo-600">

                {{-- Identity confirmation (photo appears here) --}}
                <div id="att-photo" class="att-photo is-hidden" aria-live="polite">
                    <div class="mt-5 flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="relative w-24 h-24 shrink-0 rounded-full overflow-hidden bg-indigo-100 border-4 border-white shadow">
                            <img id="att-img" alt="" class="hidden w-full h-full object-cover">
                            <span id="att-initials" class="w-full h-full grid place-items-center text-2xl font-extrabold text-indigo-700"></span>
                        </div>
                        <div class="min-w-0">
                            <p id="att-name" class="font-display text-lg font-extrabold text-slate-900 truncate"></p>
                            <p id="att-meta" class="text-sm text-slate-500"></p>
                            <p id="att-flag" class="hidden mt-1 text-xs font-semibold text-amber-700"></p>
                        </div>
                    </div>
                </div>

                {{-- Not found / result messages --}}
                <div id="att-msg" class="hidden mt-4 rounded-xl border p-4 text-sm" role="status"></div>

                <button type="submit" id="att-btn" disabled
                        class="mt-5 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 19a6 6 0 0 0-12 0"/><circle cx="9" cy="8" r="4"/><path d="M19 8v6M16 11h6"/>
                    </svg>
                    <span id="att-btn-text">Record entry</span>
                </button>
            </form>
        </section>

        {{-- 3. Live counters --}}
        <section class="w-full max-w-xl mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3" aria-label="Attendance summary">
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="grid place-items-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <p class="text-sm text-slate-600 leading-tight"><span id="c-today" class="att-stat-num block text-xl font-extrabold text-slate-900">{{ $summary['today'] }}</span> <span id="c-today-label">{{ $summary['today'] === 1 ? 'visit' : 'visits' }}</span> today</p>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="grid place-items-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>
                    </svg>
                </span>
                <p class="text-sm text-slate-600 leading-tight"><span id="c-unique" class="att-stat-num block text-xl font-extrabold text-slate-900">{{ $summary['unique'] }}</span> unique <span id="c-unique-label">{{ $summary['unique'] === 1 ? 'visitor' : 'visitors' }}</span></p>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="grid place-items-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                    </svg>
                </span>
                <p class="text-sm text-slate-600 leading-tight"><span id="c-week" class="att-stat-num block text-xl font-extrabold text-slate-900">{{ $summary['week'] }}</span> this week</p>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    const form     = document.getElementById('att-form');
    const input    = document.getElementById('att-id');
    const btn      = document.getElementById('att-btn');
    const btnText  = document.getElementById('att-btn-text');
    const photoBox = document.getElementById('att-photo');
    const img      = document.getElementById('att-img');
    const initials = document.getElementById('att-initials');
    const nameEl   = document.getElementById('att-name');
    const metaEl   = document.getElementById('att-meta');
    const flagEl   = document.getElementById('att-flag');
    const msg      = document.getElementById('att-msg');
    const csrf     = document.querySelector('meta[name="csrf-token"]').content;
    const lookupUrl  = input.dataset.lookupUrl;
    const summaryUrl = @json(route('attendance.summary'));

    const TONES = {
        ok:   'border-sky-300 bg-sky-50 text-sky-900',
        warn: 'border-amber-300 bg-amber-50 text-amber-900',
        bad:  'border-rose-300 bg-rose-50 text-rose-800',
    };

    let timer = null;      // debounce for the live lookup
    let seq = 0;           // ignore out-of-date lookup responses
    let resetTimer = null;
    let busy = false;

    function showMessage(tone, headline, detail) {
        msg.className = 'mt-4 rounded-xl border p-4 text-sm ' + TONES[tone];
        msg.replaceChildren();
        const h = document.createElement('p');
        h.className = 'font-bold';
        h.textContent = headline;
        msg.append(h);
        if (detail) {
            const d = document.createElement('p');
            d.className = 'mt-1';
            d.textContent = detail;
            msg.append(d);
        }
    }
    function hideMessage() { msg.className = 'hidden'; msg.replaceChildren(); }

    function hidePhoto() {
        photoBox.classList.add('is-hidden');
        img.removeAttribute('src');
        img.classList.add('hidden');
    }

    function showPatron(p) {
        nameEl.textContent = p.name;
        metaEl.textContent = [p.patron_type, p.id_number].filter(Boolean).join(' · ');
        if (p.photo_url) {
            img.onerror = function () { img.classList.add('hidden'); initials.textContent = p.initials || ''; initials.classList.remove('hidden'); };
            img.src = p.photo_url;
            img.alt = 'Photo of ' + p.name;
            img.classList.remove('hidden');
            initials.classList.add('hidden');
        } else {
            img.classList.add('hidden');
            initials.textContent = p.initials || '';
            initials.classList.remove('hidden');
        }
        if (p.status && p.status !== 'active') {
            flagEl.textContent = 'This ID is marked inactive.';
            flagEl.classList.remove('hidden');
        } else {
            flagEl.classList.add('hidden');
        }
        photoBox.classList.remove('is-hidden');
    }

    function setCounter(id, value) {
        const el = document.getElementById(id);
        if (el && el.textContent !== String(value)) el.textContent = value;
    }
    function applySummary(s) {
        if (!s) return;
        setCounter('c-today', s.today);
        setCounter('c-unique', s.unique);
        setCounter('c-week', s.week);
        document.getElementById('c-today-label').textContent = s.today === 1 ? 'visit' : 'visits';
        document.getElementById('c-unique-label').textContent = s.unique === 1 ? 'visitor' : 'visitors';
    }

    function setBusy(state) {
        busy = state;
        btn.disabled = state || input.value.trim() === '';
        btnText.textContent = state ? 'Recording…' : 'Record entry';
    }

    // Live lookup while typing / scanning (no page refresh).
    async function lookup(value, mine) {
        try {
            const res = await fetch(lookupUrl + '?id_number=' + encodeURIComponent(value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (mine !== seq) return;
            const data = await res.json();
            if (data.found) {
                hideMessage();
                showPatron(data);
            } else {
                hidePhoto();
                showMessage('bad', 'No student found', 'Check the ID number and try again.');
            }
        } catch (e) {
            if (mine === seq) { hidePhoto(); }
        }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        clearTimeout(resetTimer);
        const value = input.value.trim();
        btn.disabled = busy || value === '';
        seq++;
        if (value === '') { hidePhoto(); hideMessage(); return; }
        const mine = seq;
        timer = setTimeout(function () { lookup(value, mine); }, 250);
    });

    // Record entry.
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const value = input.value.trim();
        if (value === '' || busy) return;
        clearTimeout(timer);
        seq++;
        setBusy(true);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ patron_id_number: value }),
            });
            const data = await res.json().catch(function () { return {}; });

            if (res.status === 422) {
                hidePhoto();
                showMessage('bad', 'Not recorded', (data.errors && data.errors.patron_id_number && data.errors.patron_id_number[0]) || data.message || 'Invalid ID.');
                return;
            }
            if (res.status === 419) {
                // Page was left open for a long time: refresh it so the next student can check in.
                showMessage('bad', 'Refreshing…', 'Please try again in a moment.');
                setTimeout(function () { window.location.reload(); }, 800);
                return;
            }
            if (!res.ok) {
                showMessage('bad', 'Something went wrong', 'The entry may not have been recorded. Please try again.');
                return;
            }

            if (data.patron) showPatron(data.patron);
            if (data.status === 'duplicate') {
                showMessage('warn', data.headline, data.detail);
            } else {
                showMessage(data.warning ? 'warn' : 'ok', data.headline, [data.detail, data.warning].filter(Boolean).join(' '));
            }
            applySummary(data.summary);

            // Ready for the next student after a few seconds.
            input.value = '';
            clearTimeout(resetTimer);
            resetTimer = setTimeout(function () { hidePhoto(); hideMessage(); }, 5000);
        } catch (err) {
            showMessage('bad', 'Network error', 'The entry was not recorded. Check the connection and try again.');
        } finally {
            setBusy(false);
            input.focus();
        }
    });

    // Keep the counters fresh when other stations record visits.
    setInterval(async function () {
        if (document.hidden) return;
        try {
            const res = await fetch(summaryUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (res.ok) applySummary(await res.json());
        } catch (e) { /* best effort */ }
    }, 30000);

    // Clicking empty space puts the cursor back in the box (scanner ready).
    document.addEventListener('click', function (e) {
        if (!e.target.closest('a, button, input, select, textarea, label')) input.focus();
    });
})();
</script>
</body>
</html>
