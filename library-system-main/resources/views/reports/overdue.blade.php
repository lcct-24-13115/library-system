@extends('layouts.library')

@section('title', 'Overdue & On-loan - SALRC Library')
@section('width', 'max-w-7xl')

@section('content')
    @include('reports._header', [
        'title' => $scope === 'overdue' ? 'Overdue Books' : 'Books Currently On Loan',
        'subtitle' => $loans->count() . ' record(s) as of ' . now()->format('M d, Y') . ' · For manual follow-up. No fines are calculated by the system.',
    ])

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 print:hidden">
        <form method="GET" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Show</label>
                <select name="scope" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="overdue" @selected($scope === 'overdue')>Overdue only</option>
                    <option value="all" @selected($scope === 'all')>All on loan</option>
                </select>
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Generate</button>
            <button type="button" onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Print</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto print:shadow-none print:border-0">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Patron</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Contact</th>
                    <th class="p-3">Accession No.</th>
                    <th class="p-3">Title</th>
                    <th class="p-3">Issued</th>
                    <th class="p-3">Due</th>
                    <th class="p-3">Days overdue</th>
                    <th class="p-3">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($loans as $i => $loan)
                    <tr>
                        <td class="p-3 text-slate-400">{{ $i + 1 }}</td>
                        <td class="p-3 font-medium">{{ $loan->patron->name }}<div class="text-xs text-slate-500">{{ $loan->patron->id_number }}</div></td>
                        <td class="p-3">{{ $loan->patron->patron_type }}</td>
                        <td class="p-3">{{ $loan->patron->contact_no ?? '—' }}<div class="text-xs text-slate-500">{{ $loan->patron->email }}</div></td>
                        <td class="p-3 font-bold text-indigo-900">{{ $loan->book->accession_number }}</td>
                        <td class="p-3">{{ $loan->book->title }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-3 font-semibold {{ $loan->isOverdue() ? 'text-amber-700' : 'text-slate-400' }}">{{ $loan->isOverdue() ? $loan->daysOverdue() : '—' }}</td>
                        <td class="p-3">{{ $loan->remarks ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-10 text-center text-slate-400">Nothing to show. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
