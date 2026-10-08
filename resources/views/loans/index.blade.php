@extends('layouts.library')

@section('title', 'Loans - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Circulation</h2>
            <p class="text-slate-500 text-sm">Borrowing history and books currently on loan</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('loans.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">+ Issue Book</a>
            <a href="{{ route('loans.return.form') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">Return Book</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs uppercase font-bold text-slate-500">On loan</p>
            <p class="text-2xl font-extrabold text-indigo-900">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs uppercase font-bold text-slate-500">Overdue (info only)</p>
            <p class="text-2xl font-extrabold text-amber-600">{{ $stats['overdue'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs uppercase font-bold text-slate-500">Returned today</p>
            <p class="text-2xl font-extrabold text-emerald-600">{{ $stats['returned_today'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('loans.index') }}" method="GET" class="flex flex-col md:flex-row gap-2">
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Search by patron name / ID, book title or accession no..."
                   class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                @foreach(['all' => 'All', 'active' => 'On loan', 'overdue' => 'Overdue', 'returned' => 'Returned'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Filter</button>
            @if($search !== '' || $status !== 'all')
                <a href="{{ route('loans.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center justify-center transition">Reset</a>
            @endif
        </form>
    </div>

    <!-- Loans table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">Patron</th>
                    <th class="p-4">Book</th>
                    <th class="p-4">Issued</th>
                    <th class="p-4">Due</th>
                    <th class="p-4">Returned</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Remarks</th>
                    <th class="p-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($loans as $loan)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4">
                            <div class="font-medium text-slate-800">{{ $loan->patron->name }}</div>
                            <div class="text-xs text-slate-500">{{ $loan->patron->id_number }}</div>
                        </td>
                        <td class="p-4">
                            <div class="font-medium text-slate-800">{{ $loan->book->title }}</div>
                            <div class="text-xs text-indigo-900 font-bold">{{ $loan->book->accession_number }}</div>
                        </td>
                        <td class="p-4 text-slate-600">{{ $loan->issued_at->format('M d, Y') }}</td>
                        <td class="p-4 text-slate-600">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-4 text-slate-600">{{ $loan->returned_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="p-4">@include('loans._status', ['loan' => $loan])</td>
                        <td class="p-4 text-slate-600 max-w-xs truncate" title="{{ $loan->remarks }}">{{ $loan->remarks ?? '—' }}</td>
                        <td class="p-4 text-center">
                            @unless($loan->isReturned())
                                <form action="{{ route('loans.return', $loan) }}" method="POST" onsubmit="return confirm('Mark this book as returned?');">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-emerald-700 shadow-sm transition">
                                        Return
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-slate-400">
                            <p class="text-lg font-medium">No loan records found.</p>
                            <p class="text-sm mt-1">Issue a book to get started.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
