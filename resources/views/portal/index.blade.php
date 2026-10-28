@extends('layouts.portal')

@section('title', 'My Library - SALRC')

@section('content')
    @if($errors->has('reserve'))
        <div class="mb-6 p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-lg">{{ $errors->first('reserve') }}</div>
    @endif

    <div class="mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-xl text-sm text-indigo-900 flex flex-wrap items-center justify-between gap-3">
        <span>Want a book? Find it in the catalog and press <strong>Reserve</strong>. Then show your ID at the library to borrow it.</span>
        <a href="{{ route('catalog.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-1.5 rounded-lg">Browse catalog</a>
    </div>

    @php $overdueCount = $active->filter(fn ($l) => $l->isOverdue())->count(); @endphp

    <p class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $patron->id_number }} &middot; {{ $patron->patron_type }}</p>
    <h1 class="text-3xl font-extrabold text-slate-900 mb-6">{{ $patron->name }}</h1>

    <div class="grid grid-cols-3 gap-4 mb-8">
        @foreach([['Books on loan', $active->count(), 'text-indigo-600'], ['Overdue', $overdueCount, 'text-amber-600'], ['Reserved', $reservations->count(), 'text-indigo-900']] as [$label, $value, $color])
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-extrabold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <h2 class="text-lg font-bold text-slate-900 mb-2">Books I have borrowed</h2>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-8">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr><th class="p-3">Title</th><th class="p-3">Checked out</th><th class="p-3">Due</th><th class="p-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($active as $loan)
                    <tr>
                        <td class="p-3 font-medium">{{ $loan->book->title }}<div class="text-xs text-slate-500">{{ $loan->book->author }}</div></td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-3">
                            @if($loan->isOverdue())
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Overdue {{ $loan->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $loan->daysOverdue()) }}</span>
                                <div class="text-xs text-slate-500 mt-1">Please check it in at the library.</div>
                            @else
                                @php $left = (int) today()->diffInDays($loan->due_date, false); @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">{{ $left === 0 ? 'Due today' : 'Due in ' . $left . ' ' . \Illuminate\Support\Str::plural('day', $left) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-slate-400">You have no books on loan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reservations->isNotEmpty())
        <h2 class="text-lg font-bold text-slate-900 mb-2">My reservations</h2>
        <ul class="bg-white rounded-xl shadow-sm border border-slate-200 divide-y divide-slate-100 text-sm mb-8">
            @foreach($reservations as $r)
                <li class="p-3 flex justify-between gap-3">
                    <span class="font-medium">{{ $r->book->title }}</span>
                    <span class="flex items-center gap-3 whitespace-nowrap">
                        <span class="text-slate-500">Reserved {{ $r->reserved_at?->format('M d, Y') }}</span>
                        <form action="{{ route('portal.reserve.cancel', $r) }}" method="POST" onsubmit="return confirm('Cancel this request?');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Cancel</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="text-lg font-bold text-slate-900 mb-2">Recently checked in</h2>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr><th class="p-3">Title</th><th class="p-3">Checked out</th><th class="p-3">Checked in</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($history as $loan)
                    <tr>
                        <td class="p-3 font-medium">{{ $loan->book->title }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->returned_at->format('M d, Y') }}@if($loan->outcomeKey() !== 'returned') <span class="text-xs text-slate-500">({{ $loan->outcome_label }})</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-8 text-center text-slate-400">Nothing checked in yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
