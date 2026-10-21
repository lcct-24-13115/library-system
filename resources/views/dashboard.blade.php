@extends('layouts.library')

@section('title', 'Dashboard - SALRC Library')

@section('content')
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-slate-900">Dashboard</h2>
        <p class="text-slate-500 text-sm">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <!-- Stat cards (3D extruded, tilt on hover) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        @foreach([
            ['Titles in catalog', $stats['titles'], 'books', ['#2563eb', '#60a5fa', '#1e40af', 'rgba(37,99,235,.35)'], route('books.index')],
            ['Copies available', $stats['available'] . ' / ' . $stats['copies'], 'library', ['#0284c7', '#38bdf8', '#075985', 'rgba(14,165,233,.35)'], route('books.index')],
            ['Books on loan', $stats['on_loan'], 'issue', ['#4f46e5', '#818cf8', '#3730a3', 'rgba(79,70,229,.33)'], route('loans.index', ['status' => 'active'])],
            ['Overdue (info only)', $stats['overdue'], 'loans', ['#d97706', '#fbbf24', '#92400e', 'rgba(245,158,11,.38)'], route('loans.index', ['status' => 'overdue'])],
            ['Issued today', $stats['issued_today'], 'arrow', ['#1d4ed8', '#60a5fa', '#172554', 'rgba(29,78,216,.33)'], route('loans.index')],
            ['Returned today', $stats['returned_today'], 'return', ['#0891b2', '#67e8f9', '#155e75', 'rgba(8,145,178,.33)'], route('loans.index')],
            ['Active patrons', $stats['patrons'], 'users', ['#2563eb', '#93c5fd', '#1e3a8a', 'rgba(37,99,235,.33)'], route('patrons.index')],
        ] as [$label, $value, $icon, $c, $href])
            <a href="{{ $href }}" class="stat3d" data-tilt="7" style="--a:{{ $c[0] }};--a2:{{ $c[1] }};--edge:{{ $c[2] }};--glow:{{ $c[3] }}">
                <span class="stat3d__icon"><x-icon :name="$icon" :size="22" /></span>
                <p class="stat3d__label">{{ $label }}</p>
                <p class="stat3d__value text-slate-900">{{ $value }}</p>
            </a>
        @endforeach

        <div class="bg-indigo-900 p-5 rounded-xl flex flex-col justify-center gap-3" data-tilt="5">
            <a href="{{ route('loans.create') }}" class="bg-white text-indigo-900 text-center font-semibold text-sm py-2 rounded-lg hover:bg-indigo-50" style="box-shadow:0 5px 0 #b9c9e8,0 12px 16px -6px rgba(0,0,0,.4)">Issue Book</a>
            <a href="{{ route('loans.return.form') }}" class="bg-indigo-700 text-white text-center font-semibold text-sm py-2 rounded-lg hover:bg-indigo-600">Return Book</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- 7-day chart -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6" data-tilt="2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Circulation — last 7 days</h3>
                <div class="flex gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-indigo-500 inline-block"></span> Issued</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-sky-400 inline-block"></span> Returned</span>
                </div>
            </div>
            <div class="flex items-end gap-3 h-48 chart-floor" style="perspective:900px">
                @foreach($chart as $day)
                    <div class="flex-1 flex flex-col items-center h-full justify-end" title="{{ $day['date'] }}: {{ $day['issued'] }} issued, {{ $day['returned'] }} returned">
                        <div class="flex items-end gap-2 w-full justify-center flex-1">
                            <div class="bar3d bar3d--issued" style="height: {{ $day['issued'] / $chartMax * 100 }}%; min-height: {{ $day['issued'] ? '4px' : '0' }}; animation-delay: {{ $loop->index * 70 }}ms"></div>
                            <div class="bar3d bar3d--returned" style="height: {{ $day['returned'] / $chartMax * 100 }}%; min-height: {{ $day['returned'] ? '4px' : '0' }}; animation-delay: {{ $loop->index * 70 + 40 }}ms"></div>
                        </div>
                        <div class="text-xs text-slate-500 mt-2">{{ $day['label'] }}</div>
                        <div class="text-[10px] text-slate-400">{{ $day['issued'] }} / {{ $day['returned'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Popular -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-900 mb-3">Most borrowed titles</h3>
            <ol class="space-y-3 text-sm">
                @forelse($popular as $i => $book)
                    <li class="flex gap-3">
                        <span class="text-indigo-900 font-extrabold w-5">{{ $i + 1 }}</span>
                        <a href="{{ route('books.show', $book->id) }}" class="flex-1 hover:text-indigo-700">
                            <div class="font-medium text-slate-800">{{ $book->title }}</div>
                            <div class="text-xs text-slate-500">{{ $book->author }}</div>
                        </a>
                        <span class="text-xs font-bold text-slate-600">{{ $book->loans_count }}&times;</span>
                    </li>
                @empty
                    <li class="text-slate-400">No borrowing yet.</li>
                @endforelse
            </ol>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Overdue -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-slate-900">Longest overdue</h3>
                <a href="{{ route('reports.overdue') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Full list &rarr;</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($overdue as $loan)
                    <li class="py-2 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-medium text-slate-800">{{ $loan->patron->name }}</div>
                            <div class="text-xs text-slate-500">{{ $loan->book->title }}</div>
                        </div>
                        @include('loans._status', ['loan' => $loan])
                    </li>
                @empty
                    <li class="py-4 text-slate-400">Nothing overdue. 🎉</li>
                @endforelse
            </ul>
        </div>

        <!-- Recent -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-slate-900">Recent transactions</h3>
                <a href="{{ route('loans.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">All loans &rarr;</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($recent as $loan)
                    <li class="py-2 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-medium text-slate-800">{{ $loan->patron->name }}</div>
                            <div class="text-xs text-slate-500">{{ $loan->book->title }} &middot; {{ $loan->issued_at->diffForHumans() }}</div>
                        </div>
                        @include('loans._status', ['loan' => $loan])
                    </li>
                @empty
                    <li class="py-4 text-slate-400">No transactions yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
