@extends('layouts.library')

@section('title', 'Overdue List - SALRC Library')
@section('width', 'max-w-6xl')

{{-- Phase 5: overdue list for offline follow-up by staff. Display only; print with the browser. --}}
@section('content')
    @include('reports._header', [
        'title'    => 'Overdue List',
        'subtitle' => $loans->count() . ' ' . \Illuminate\Support\Str::plural('book', $loans->count()) . ' overdue as of ' . now()->format('M d, Y') . ' · For offline follow-up by staff.',
    ])

    <div class="mb-4 print:hidden">
        <button type="button" onclick="window.print()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
            Print
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto print:shadow-none print:border-0">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Patron</th>
                    <th class="p-3">Book</th>
                    <th class="p-3">Due date</th>
                    <th class="p-3">Days overdue</th>
                    <th class="p-3">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($loans as $i => $loan)
                    <tr class="break-inside-avoid">
                        <td class="p-3 text-slate-400">{{ $i + 1 }}</td>
                        <td class="p-3 font-medium">
                            {{ $loan->patron->name }}
                            <div class="text-xs text-slate-500">{{ $loan->patron->id_number }} &middot; {{ $loan->patron->patron_type }}</div>
                        </td>
                        <td class="p-3">
                            {{ $loan->book->title }}
                            <div class="text-xs text-slate-500">{{ $loan->book->accession_number }}</div>
                        </td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-3 font-semibold text-amber-700">{{ $loan->daysOverdue() }}</td>
                        <td class="p-3">
                            @if($loan->patron->contact_no || $loan->patron->email)
                                @if($loan->patron->contact_no)<div>{{ $loan->patron->contact_no }}</div>@endif
                                @if($loan->patron->email)<div class="text-xs text-slate-500">{{ $loan->patron->email }}</div>@endif
                            @else
                                <span class="text-slate-300">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-slate-400">No overdue books. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
