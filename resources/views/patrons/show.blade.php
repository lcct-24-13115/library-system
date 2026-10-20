@extends('layouts.library')

@section('title', $patron->name . ' - SALRC Library')
@section('width', 'max-w-5xl')

@section('content')
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $patron->id_number }}</p>
            <h2 class="text-3xl font-extrabold text-slate-900">{{ $patron->name }}</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold">{{ $patron->patron_type }}</span>
                @if($patron->isActive())
                    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-semibold">Active</span>
                @else
                    <span class="bg-slate-200 text-slate-600 px-2.5 py-1 rounded-full text-xs font-semibold">Inactive</span>
                @endif
            </div>
            <p class="text-slate-400 text-xs hidden print:block mt-2">SALRC Library System &middot; Printed {{ now()->format('M d, Y h:i A') }}</p>
        </div>
        <div class="flex flex-wrap gap-2 print:hidden">
            <a href="{{ route('patrons.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">&larr; Patrons</a>
            <a href="{{ route('loans.create', ['patron_id_number' => $patron->id_number]) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm">Issue Book</a>
            <a href="{{ route('reservations.create', ['patron_id_number' => $patron->id_number]) }}" class="px-4 py-2 bg-indigo-100 text-indigo-800 rounded-lg text-sm font-semibold hover:bg-indigo-200 shadow-sm">Reserve Book</a>
            @if(auth()->user()->canManage())
                <a href="{{ route('patrons.edit', $patron) }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 shadow-sm">Edit</a>
            @endif
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900 shadow-sm">Print</button>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['Books on loan', $stats['active_loans'], 'text-indigo-600'],
            ['Overdue (info only)', $stats['overdue'], 'text-amber-600'],
            ['Total borrowed', $stats['total_loans'], 'text-slate-800'],
            ['Visits this month', $stats['visits_month'], 'text-emerald-600'],
            ['Pending reservations', $stats['pending'], 'text-indigo-900'],
        ] as [$label, $value, $color])
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs uppercase font-bold text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-extrabold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            {{-- Contact details --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">Contact Details</h3>
                <dl class="grid grid-cols-3 gap-y-2 text-sm">
                    <dt class="text-slate-500 font-semibold">Email</dt>
                    <dd class="col-span-2 text-slate-800 break-words">
                        @if($patron->email)<a href="mailto:{{ $patron->email }}" class="hover:text-indigo-700">{{ $patron->email }}</a>@else <span class="text-slate-400">&mdash;</span> @endif
                    </dd>
                    <dt class="text-slate-500 font-semibold">Contact no.</dt>
                    <dd class="col-span-2 text-slate-800">{{ $patron->contact_no ?: '—' }}</dd>
                    <dt class="text-slate-500 font-semibold">Registered</dt>
                    <dd class="col-span-2 text-slate-800">{{ $patron->created_at?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="text-slate-500 font-semibold">Total visits</dt>
                    <dd class="col-span-2 text-slate-800">{{ $stats['visits_total'] }}</dd>
                </dl>
            </div>

            {{-- Reservations --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">Active Reservations</h3>
                <ul class="space-y-3 text-sm">
                    @forelse($pending as $r)
                        <li>
                            <a href="{{ route('books.show', $r->book->id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $r->book->title }}</a>
                            <div class="text-xs text-slate-500">
                                Reserved {{ $r->reserved_at->format('M d, Y') }}
                                @isset($positions[$r->id]) &middot; queue #{{ $positions[$r->id] }} @endisset
                                &middot; {{ $r->book->available_copies > 0 ? $r->book->available_copies . ' on shelf' : 'all on loan' }}
                            </div>
                        </li>
                    @empty
                        <li class="text-slate-400">No pending reservations.</li>
                    @endforelse
                </ul>
                @if($pastReservations->isNotEmpty())
                    <h4 class="text-xs font-bold text-slate-500 uppercase mt-5 mb-2">Recently closed</h4>
                    <ul class="space-y-2 text-xs text-slate-600">
                        @foreach($pastReservations as $r)
                            <li class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ $r->book->title }}</span>
                                @include('reservations._status', ['reservation' => $r])
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p class="mt-4 text-xs print:hidden"><a href="{{ route('reservations.index', ['status' => 'all', 'search' => $patron->id_number]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">All reservations &rarr;</a></p>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- Active loans --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 pt-5 pb-2 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider">Active Loans</h3>
                    <a href="{{ route('loans.return.form', ['search' => $patron->id_number]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs print:hidden">Return / renew &rarr;</a>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-y border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                        <tr><th class="p-3">Book</th><th class="p-3">Issued</th><th class="p-3">Due</th><th class="p-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($activeLoans as $loan)
                            <tr>
                                <td class="p-3">
                                    <a href="{{ route('books.show', $loan->book->id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $loan->book->title }}</a>
                                    <div class="text-xs text-slate-500">{{ $loan->book->accession_number }}@if($loan->renewal_count) &middot; renewed {{ $loan->renewal_count }}&times; @endif</div>
                                </td>
                                <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $loan->due_date->format('M d, Y') }}</td>
                                <td class="p-3">@include('loans._status', ['loan' => $loan])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-slate-400">No books currently on loan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Borrowing history --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 pt-5 pb-2 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider">Borrowing History <span class="text-slate-400 normal-case font-normal">(latest returned)</span></h3>
                    <a href="{{ route('loans.index', ['search' => $patron->id_number]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs print:hidden">All loans &rarr;</a>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-y border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                        <tr><th class="p-3">Book</th><th class="p-3">Issued</th><th class="p-3">Returned</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($history as $loan)
                            <tr>
                                <td class="p-3">
                                    <a href="{{ route('books.show', $loan->book->id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $loan->book->title }}</a>
                                    <div class="text-xs text-slate-500">{{ $loan->book->accession_number }}</div>
                                    @if($loan->remarks || $loan->notes->isNotEmpty())<div class="mt-0.5">@include('loans._notes', ['loan' => $loan])</div>@endif
                                </td>
                                <td class="p-3 whitespace-nowrap">{{ $loan->issued_at->format('M d, Y') }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $loan->returned_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-6 text-center text-slate-400">No returned books yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Attendance history --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 pt-5 pb-2 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider">Attendance History <span class="text-slate-400 normal-case font-normal">(latest visits)</span></h3>
                    <a href="{{ route('attendance.history', ['search' => $patron->id_number]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs print:hidden">All visits &rarr;</a>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-y border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                        <tr><th class="p-3">Date</th><th class="p-3">Time</th><th class="p-3">Remarks</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($visits as $v)
                            <tr>
                                <td class="p-3 whitespace-nowrap">{{ $v->visited_at->format('D, M d, Y') }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $v->visited_at->format('h:i A') }}</td>
                                <td class="p-3 text-slate-500">{{ $v->remarks ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-6 text-center text-slate-400">No visits logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
