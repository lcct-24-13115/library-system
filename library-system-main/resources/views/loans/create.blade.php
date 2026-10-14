@extends('layouts.library')

@section('title', 'Issue Book - SALRC Library')
@section('width', 'max-w-3xl')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900">Issue Book</h2>
            <p class="text-slate-500 text-sm">Type or scan the patron's ID number and the book's accession number.</p>
        </div>
        <a href="{{ route('loans.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
            View all loans &rarr;
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('loans.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Patron ID Number</label>
                    <input type="text" name="patron_id_number" value="{{ old('patron_id_number') }}" required autofocus autocomplete="off"
                           placeholder="e.g. 2026-0001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Book Accession Number</label>
                    <input type="text" name="accession_number" value="{{ old('accession_number') }}" required autocomplete="off"
                           placeholder="e.g. ACC-2026-001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">No. of Days Out</label>
                    <input type="number" name="loan_days" min="1" max="365" value="{{ old('loan_days', $loanDays) }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Remarks (Optional)</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" maxlength="500"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-between">
                <p class="text-xs text-slate-500">
                    No fines are calculated and overdue items never block borrowing.
                    Fines and payments are handled manually by the librarian.
                </p>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition whitespace-nowrap">
                    Issue Book
                </button>
            </div>
        </form>
    </div>

    <h3 class="text-lg font-bold text-slate-900 mt-10 mb-3">Latest Transactions</h3>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">Patron</th>
                    <th class="p-3">Book</th>
                    <th class="p-3">Due</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($recent as $loan)
                    <tr>
                        <td class="p-3">{{ $loan->patron->name }}</td>
                        <td class="p-3">{{ $loan->book->title }}</td>
                        <td class="p-3">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-3">@include('loans._status', ['loan' => $loan])</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-slate-400">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
