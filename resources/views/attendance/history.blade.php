@extends('layouts.library')

@section('title', 'Visitor History - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Visitor History</h2>
            <p class="text-slate-500 text-sm">
                {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('visit', $total) }}
                @if($from || $to) &middot; {{ $from ?: 'start' }} to {{ $to ?: 'today' }} @endif
                @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
            </p>
            <p class="text-slate-400 text-xs hidden print:block">SALRC Library System &middot; Printed {{ now()->format('M d, Y h:i A') }}</p>
        </div>
        <div class="flex gap-2 print:hidden">
            <a href="{{ route('attendance.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">&larr; Record entry</a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900 shadow-sm">Print</button>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 print:hidden">
        <form action="{{ route('attendance.history') }}" method="GET" class="flex flex-col md:flex-row gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by patron name or ID number..."
                   class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <input type="date" name="from" value="{{ $from }}" title="From"
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <input type="date" name="to" value="{{ $to }}" title="To"
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Filter</button>
            @if($search !== '' || $from || $to)
                <a href="{{ route('attendance.history') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center justify-center transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">Date</th>
                    <th class="p-4">Time</th>
                    <th class="p-4">ID Number</th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Type</th>
                    <th class="p-4">Remarks</th>
                    <th class="p-4 print:hidden"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($visits as $v)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 whitespace-nowrap">{{ $v->visited_at->format('M d, Y') }}</td>
                        <td class="p-4 whitespace-nowrap">{{ $v->visited_at->format('h:i A') }}</td>
                        <td class="p-4 font-bold text-indigo-900">{{ $v->patron->id_number }}</td>
                        <td class="p-4"><a href="{{ route('patrons.show', $v->patron) }}" class="hover:text-indigo-700">{{ $v->patron->name }}</a></td>
                        <td class="p-4">{{ $v->patron->patron_type }}</td>
                        <td class="p-4 text-slate-500">{{ $v->remarks ?? '—' }}</td>
                        <td class="p-4 text-right print:hidden">
                            @if(auth()->user()->canManage())
                                <form action="{{ route('attendance.destroy', $v) }}" method="POST" onsubmit="return confirm('Remove this visit entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-slate-400">No visits found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 print:hidden">{{ $visits->links() }}</div>
@endsection
