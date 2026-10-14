@extends('layouts.library')

@section('title', 'Reservations - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Reservations</h2>
            <p class="text-slate-500 text-sm">Holds placed by patrons. Statuses are updated manually.</p>
        </div>
        <a href="{{ route('reservations.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">+ New Reservation</a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        @foreach([['pending', 'Pending', 'text-amber-600'], ['fulfilled', 'Fulfilled', 'text-emerald-600'], ['cancelled', 'Cancelled', 'text-slate-600']] as [$key, $label, $color])
            <a href="{{ route('reservations.index', ['status' => $key]) }}"
               class="bg-white p-4 rounded-xl shadow-sm border {{ $status === $key ? 'border-indigo-400' : 'border-slate-200' }} hover:border-indigo-300 transition">
                <p class="text-xs uppercase font-bold text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-extrabold {{ $color }}">{{ $counts[$key] }}</p>
            </a>
        @endforeach
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('reservations.index') }}" method="GET" class="flex flex-col sm:flex-row gap-2">
            <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                @foreach(['pending' => 'Pending', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by patron name/ID, book title or accession no..."
                   class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Search</button>
            @if($search !== '' || $status !== 'pending')
                <a href="{{ route('reservations.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center justify-center transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">Patron</th>
                    <th class="p-4">Book</th>
                    <th class="p-4">Reserved</th>
                    <th class="p-4">Queue</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($reservations as $r)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4">
                            <a href="{{ route('patrons.show', $r->patron) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $r->patron->name }}</a>
                            <div class="text-xs text-slate-500">{{ $r->patron->id_number }}</div>
                        </td>
                        <td class="p-4">
                            <a href="{{ route('books.show', $r->book->id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $r->book->title }}</a>
                            <div class="text-xs text-slate-500">
                                {{ $r->book->accession_number }} &middot;
                                @if($r->book->available_copies > 0)
                                    <span class="text-emerald-600 font-semibold">{{ $r->book->available_copies }} on shelf</span>
                                @else
                                    <span class="text-rose-600 font-semibold">all on loan</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-4 text-slate-600 whitespace-nowrap">
                            {{ $r->reserved_at->format('M d, Y') }}
                            <div class="text-xs text-slate-400">{{ $r->reserved_at->format('h:i A') }}</div>
                            @if($r->remarks)<div class="text-xs text-slate-500 mt-1 max-w-[14rem] whitespace-normal">{{ $r->remarks }}</div>@endif
                        </td>
                        <td class="p-4 font-semibold text-slate-700">
                            @if($r->isPending() && isset($positions[$r->id]))
                                #{{ $positions[$r->id] }}
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td class="p-4">
                            @include('reservations._status', ['reservation' => $r])
                            @if($r->fulfilled_at)<div class="text-xs text-slate-400 mt-1">{{ $r->fulfilled_at->format('M d, Y') }}</div>@endif
                            @if($r->cancelled_at)<div class="text-xs text-slate-400 mt-1">{{ $r->cancelled_at->format('M d, Y') }}</div>@endif
                        </td>
                        <td class="p-4 text-center whitespace-nowrap">
                            @if($r->isPending())
                                <a href="{{ route('loans.create', ['patron_id_number' => $r->patron->id_number, 'accession_number' => $r->book->accession_number]) }}"
                                   class="inline-block bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-indigo-700 shadow-sm transition">Issue</a>
                                <form action="{{ route('reservations.fulfill', $r) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-emerald-700 shadow-sm transition">Mark Fulfilled</button>
                                </form>
                                <form action="{{ route('reservations.cancel', $r) }}" method="POST" class="inline-block" onsubmit="return confirm('Cancel this reservation?');">
                                    @csrf
                                    <button type="submit" class="bg-rose-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-rose-700 shadow-sm transition">Cancel</button>
                                </form>
                            @else
                                <span class="text-slate-300">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-slate-400">
                            <p class="text-lg font-medium">No reservations found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reservations->links() }}</div>
@endsection
