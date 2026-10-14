@extends('layouts.library')

@section('title', 'Return Book - SALRC Library')

@section('content')
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-slate-900">Return Book</h2>
        <p class="text-slate-500 text-sm">Search by patron ID, patron name, accession number or title, then click Return.</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('loans.return.form') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ $search }}" autofocus autocomplete="off"
                   placeholder="Scan or type a patron ID / accession number..."
                   class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Search</button>
            @if($search !== '')
                <a href="{{ route('loans.return.form') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center transition">Reset</a>
            @endif
        </form>
    </div>

    <p class="text-xs text-slate-500 mb-3">
        Overdue items are shown for information only. No fines are calculated. Collect any fine manually and note it in Remarks if you wish.
    </p>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">Patron</th>
                    <th class="p-4">Book</th>
                    <th class="p-4">Due</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Remarks (optional)</th>
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
                        <td class="p-4 text-slate-600">{{ $loan->due_date->format('M d, Y') }}</td>
                        <td class="p-4">@include('loans._status', ['loan' => $loan])</td>
                        <td class="p-4" colspan="2">
                            <form action="{{ route('loans.return', $loan) }}" method="POST" class="flex gap-2 items-center">
                                @csrf
                                <input type="text" name="remarks" maxlength="500" value="{{ $loan->remarks }}"
                                       placeholder="e.g. fine collected offline"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-500">
                                <button type="submit" class="bg-emerald-600 text-white px-4 py-1.5 rounded-md text-xs font-semibold hover:bg-emerald-700 shadow-sm transition whitespace-nowrap">
                                    Return
                                </button>
                            </form>
                            <form action="{{ route('loans.renew', $loan) }}" method="POST" class="flex gap-2 items-center mt-2">
                                @csrf
                                <label class="text-xs text-slate-500 whitespace-nowrap">Renew for</label>
                                <input type="number" name="days" min="1" max="365" value="{{ config('library.loan_days', 7) }}"
                                       class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-indigo-500">
                                <span class="text-xs text-slate-500">days</span>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-xs font-semibold hover:bg-indigo-700 shadow-sm transition">
                                    Renew
                                </button>
                                @if($loan->renewal_count > 0)
                                    <span class="text-xs text-indigo-600">Renewed {{ $loan->renewal_count }}&times;</span>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-slate-400">
                            <p class="text-lg font-medium">No books on loan{{ $search !== '' ? ' match your search' : '' }}.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
