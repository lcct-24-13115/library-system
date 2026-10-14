@extends('layouts.library')

@section('title', 'Loan History - SALRC Library')
@section('width', 'max-w-7xl')

@section('content')
    @include('reports._header', [
        'title' => 'Loan History',
        'subtitle' => $from->format('M d, Y') . ' – ' . $to->format('M d, Y') . ' · ' . $loans->count() . ' record(s)',
    ])

    @include('reports._range', ['withStatus' => true, 'status' => $status])

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto print:shadow-none print:border-0">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Issued</th>
                    <th class="p-3">Patron</th>
                    <th class="p-3">Accession No.</th>
                    <th class="p-3">Title</th>
                    <th class="p-3">Due</th>
                    <th class="p-3">Returned</th>
                    <th class="p-3">Renewals</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($loans as $i => $loan)
                    <tr>
                        <td class="p-3 text-slate-400">{{ $i + 1 }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                        <td class="p-3">{{ $loan->patron->name }}<div class="text-xs text-slate-500">{{ $loan->patron->id_number }}</div></td>
                        <td class="p-3 font-bold text-indigo-900">{{ $loan->book->accession_number }}</td>
                        <td class="p-3">{{ $loan->book->title }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->returned_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="p-3">{{ $loan->renewal_count ?: '—' }}</td>
                        <td class="p-3">@include('loans._status', ['loan' => $loan])</td>
                        <td class="p-3">{{ $loan->remarks ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-10 text-center text-slate-400">No transactions in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
