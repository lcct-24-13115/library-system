@extends('layouts.library')

@section('title', 'Issue Book - SALRC Library')
@section('width', 'max-w-6xl')

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

    @php
        // Phase 5: after an issue the patron stays; the cursor goes back to the book box.
        // Focus the patron box only when it is empty or the patron was the problem.
        $patronValue = old('patron_id_number', request('patron_id_number', $sessionPatron?->id_number));
        $focusPatron = blank($patronValue) || $errors->has('patron_id_number');
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('loans.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Patron ID Number</label>
                    <input type="text" name="patron_id_number" value="{{ $patronValue }}" required @if($focusPatron) autofocus @endif autocomplete="off"
                           placeholder="e.g. 2026-0001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Book Accession Number</label>
                    <input type="text" name="accession_number" value="{{ old('accession_number', request('accession_number')) }}" required @unless($focusPatron) autofocus @endunless autocomplete="off"
                           placeholder="e.g. ACC-2026-001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            {{-- v3: live patron / book preview (AJAX) --}}
            @include('partials.lookup-preview', ['mode' => 'issue'])

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

            @if(auth()->check() && auth()->user()->canManage())
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="override_limit" value="1" @checked(old('override_limit'))
                           class="mt-0.5 rounded border-slate-300">
                    <span><span class="font-semibold">Override borrow limit</span>
                        <span class="text-xs text-slate-500">(only if the patron is at the limit; a remark is required)</span></span>
                </label>
            @endif

            <div class="pt-2 flex items-center justify-between">
                <p class="text-xs text-slate-500">
                    No fines are calculated and overdue items never block borrowing.
                    Fines and payments are handled manually by the librarian.
                    A patron may borrow up to their type's limit and not the same title twice.
                </p>
                <div class="flex items-center gap-2">
                    {{-- Phase 5: Done clears the patron and the session list (separate form below). --}}
                    <button type="submit" form="issue-done-form" formnovalidate
                            class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg text-sm font-semibold hover:bg-slate-50 transition whitespace-nowrap">
                        Done
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition whitespace-nowrap">
                        Issue Book
                    </button>
                </div>
            </div>
        </form>
        <form id="issue-done-form" action="{{ route('loans.issue.done') }}" method="POST" class="hidden">@csrf</form>
    </div>

    {{-- Phase 5: books issued to this patron in this session --}}
    <aside id="issued-session" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5"
           data-patron="{{ $sessionPatron?->id_number }}">
        <h3 class="text-sm font-bold uppercase text-slate-600 tracking-wide">Issued this session</h3>

        @if($sessionPatron)
            <p class="mt-2 font-semibold text-slate-900">{{ $sessionPatron->name }}</p>
            <p class="text-xs text-slate-500">{{ $sessionPatron->id_number }}</p>
            <p id="issued-stale" class="hidden mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded p-2">
                The patron ID above is different. Click Done to start a new patron.
            </p>
            <p class="mt-3 text-xs text-slate-500">{{ $sessionLoans->count() }} {{ \Illuminate\Support\Str::plural('book', $sessionLoans->count()) }}</p>
            <ul class="mt-1 divide-y divide-slate-100">
                @foreach($sessionLoans as $issued)
                    <li class="py-2 text-sm">
                        <p class="font-medium text-slate-800">{{ $issued->book->title }}</p>
                        <p class="text-xs text-slate-500">{{ $issued->book->accession_number }} &middot; due {{ $issued->due_date->format('M d, Y') }}</p>
                        @if($issued->isVoided())
                            <span class="inline-block mt-1 bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full text-xs font-semibold">Voided</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-2 text-sm text-slate-400">Nothing issued yet. Books you issue to a patron will be listed here until you click Done.</p>
        @endif
    </aside>
    </div>

    <script>
    (function () {
        // If the patron box no longer matches the session patron, say so (the list is for the earlier patron).
        const panel = document.getElementById('issued-session');
        const input = document.querySelector('input[name="patron_id_number"]');
        const note  = document.getElementById('issued-stale');
        if (!panel || !input || !note || !panel.dataset.patron) return;
        const check = function () {
            note.classList.toggle('hidden', input.value.trim() === panel.dataset.patron);
        };
        input.addEventListener('input', check);
        check();
    })();
    </script>

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
