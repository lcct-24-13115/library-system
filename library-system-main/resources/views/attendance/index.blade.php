@extends('layouts.library')

@section('title', 'Attendance - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Attendance</h2>
            <p class="text-slate-500 text-sm">Scan a patron ID to log a library visit.</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('attendance.index') }}" method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Show</button>
                @unless($isToday)
                    <a href="{{ route('attendance.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm transition">Today</a>
                @endunless
            </form>
            <a href="{{ route('attendance.history') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm whitespace-nowrap">Visitor history &rarr;</a>
        </div>
    </div>

    {{-- Scan station --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6 print:hidden">
        <form id="scan-form" action="{{ route('attendance.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" id="scan-input" name="patron_id_number" required autofocus autocomplete="off"
                   placeholder="Scan or type the patron ID number, then press Enter"
                   class="w-full border-2 border-indigo-300 rounded-lg px-4 py-3 text-lg focus:outline-none focus:border-indigo-600">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition whitespace-nowrap">Log Visit</button>
        </form>
        <div id="scan-result" class="hidden mt-4 rounded-lg border p-4"></div>
        @unless($isToday)
            <p class="text-xs text-amber-700 mt-3">You are viewing {{ $date->format('M d, Y') }}. New scans are always recorded with the current date and time.</p>
        @endunless
    </div>

    {{-- Everything in here is re-fetched after each scan --}}
    <div id="attendance-live">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">Visits {{ $isToday ? 'today' : $date->format('M d') }}</p>
                <p class="text-2xl font-extrabold text-indigo-900">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">Unique visitors</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ $stats['unique'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">Peak hour</p>
                <p class="text-2xl font-extrabold text-emerald-600">{{ $peakHour ? $peakHour['label'] : '—' }}</p>
                @if($peakHour)<p class="text-xs text-slate-400">{{ $peakHour['count'] }} {{ \Illuminate\Support\Str::plural('visit', $peakHour['count']) }}</p>@endif
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">By patron type</p>
                <div class="text-sm text-slate-700 mt-1 space-y-0.5">
                    @forelse($stats['by_type'] as $type => $count)
                        <div class="flex justify-between"><span>{{ $type }}</span><span class="font-bold">{{ $count }}</span></div>
                    @empty
                        <span class="text-slate-400">No visits yet.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Hourly chart --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900 mb-4">Visitors per hour &mdash; {{ $date->format('l, M d, Y') }}</h3>
                <div class="flex items-end gap-1.5 h-48">
                    @foreach($hourly as $h)
                        <div class="flex-1 flex flex-col items-center h-full justify-end" title="{{ $h['label'] }}: {{ $h['count'] }} {{ \Illuminate\Support\Str::plural('visit', $h['count']) }}">
                            <div class="text-[10px] text-slate-500 mb-1">{{ $h['count'] ?: '' }}</div>
                            <div class="w-full {{ $peakHour && $peakHour['hour'] === $h['hour'] ? 'bg-emerald-500' : 'bg-indigo-500' }} rounded-t"
                                 style="height: {{ $h['count'] / $hourlyMax * 85 }}%; min-height: {{ $h['count'] ? '4px' : '0' }}"></div>
                            <div class="text-[10px] text-slate-500 mt-2">{{ $h['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 14-day totals --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900 mb-3">Last 14 days</h3>
                <ul class="space-y-1.5 text-sm">
                    @foreach($dailyChart->reverse() as $d)
                        <li class="flex items-center gap-2" title="{{ $d['dow'] }} {{ $d['label'] }}: {{ $d['count'] }}">
                            <a href="{{ route('attendance.index', ['date' => $d['date']]) }}" class="w-14 text-xs text-slate-500 hover:text-indigo-700">{{ $d['label'] }}</a>
                            <div class="flex-1 bg-slate-100 rounded h-3">
                                <div class="bg-indigo-400 h-3 rounded" style="width: {{ $d['count'] / $dailyMax * 100 }}%"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-bold text-slate-600">{{ $d['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Day log --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                <h3 class="font-bold text-slate-900">{{ $isToday ? "Today's" : $date->format('M d') }} log <span class="text-slate-400 font-normal text-sm">(latest {{ $visits->count() }} of {{ $stats['total'] }})</span></h3>
                <a href="{{ route('attendance.history', ['from' => $date->toDateString(), 'to' => $date->toDateString()]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm print:hidden">Full log &rarr;</a>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="p-3">Time</th>
                        <th class="p-3">ID Number</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($visits as $v)
                        <tr>
                            <td class="p-3 whitespace-nowrap">{{ $v->visited_at->format('h:i A') }}</td>
                            <td class="p-3 font-bold text-indigo-900">{{ $v->patron->id_number }}</td>
                            <td class="p-3"><a href="{{ route('patrons.show', $v->patron) }}" class="hover:text-indigo-700">{{ $v->patron->name }}</a></td>
                            <td class="p-3">{{ $v->patron->patron_type }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-slate-400">No visits logged for this day.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    (function () {
        const form   = document.getElementById('scan-form');
        const input  = document.getElementById('scan-input');
        const result = document.getElementById('scan-result');
        const live   = document.getElementById('attendance-live');
        const csrf   = document.querySelector('meta[name="csrf-token"]').content;

        const TONES = {
            ok:   'border-emerald-300 bg-emerald-50 text-emerald-900',
            warn: 'border-amber-300 bg-amber-50 text-amber-900',
            bad:  'border-rose-300 bg-rose-50 text-rose-800',
        };

        function show(tone, headline, detail, extra) {
            result.className = 'mt-4 rounded-lg border p-4 ' + TONES[tone];
            result.replaceChildren();
            const h = document.createElement('p');
            h.className = 'text-lg font-bold';
            h.textContent = headline;
            result.append(h);
            [detail, extra].filter(Boolean).forEach(function (t) {
                const p = document.createElement('p');
                p.className = 'text-sm mt-1';
                p.textContent = t;
                result.append(p);
            });
        }

        async function refreshPanel() {
            try {
                const res  = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                const doc  = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('attendance-live');
                if (fresh) live.innerHTML = fresh.innerHTML;
            } catch (e) { /* stats refresh is best-effort; the visit is already saved */ }
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const value = input.value.trim();
            if (value === '') return;
            input.value = '';
            input.focus();

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
                    const msg = (data.errors && data.errors.patron_id_number && data.errors.patron_id_number[0]) || data.message || 'Invalid ID.';
                    show('bad', 'Not recorded: ' + value, msg);
                    return;
                }
                if (res.status === 419) {
                    show('bad', 'Session expired', 'Reload this page and log in again, then scan again.');
                    return;
                }
                if (!res.ok) {
                    show('bad', 'Something went wrong', 'The visit may not have been recorded. Please scan again.');
                    return;
                }

                if (data.status === 'duplicate') {
                    show('warn', data.headline, data.detail);
                } else {
                    show(data.warning ? 'warn' : 'ok', data.headline, data.detail, data.warning);
                }
                refreshPanel();
            } catch (err) {
                show('bad', 'Network error', 'The visit was not recorded. Check the connection and scan again.');
            }
        });

        // Keep the scanner ready: clicking empty space puts the cursor back in the box.
        document.addEventListener('click', function (e) {
            if (!e.target.closest('a, button, input, select, textarea, label')) input.focus();
        });
    })();
    </script>
@endsection
