@extends('layouts.library')

@section('title', 'Reserve Book - SALRC Library')
@section('width', 'max-w-3xl')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900">Reserve Book</h2>
            <p class="text-slate-500 text-sm">Place a hold for a patron. The book can be on loan or on the shelf.</p>
        </div>
        <a href="{{ route('reservations.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
            View reservations &rarr;
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('reservations.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Patron ID Number</label>
                    <input type="text" name="patron_id_number" value="{{ old('patron_id_number', request('patron_id_number')) }}" required autocomplete="off"
                           @if(! request('patron_id_number') && ! old('patron_id_number')) autofocus @endif
                           placeholder="e.g. 2026-0001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Book Accession Number</label>
                    <input type="text" name="accession_number" value="{{ old('accession_number', request('accession_number')) }}" required autocomplete="off"
                           @if(request('patron_id_number') || old('patron_id_number')) autofocus @endif
                           placeholder="e.g. ACC-2026-001"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            @include('partials.lookup-preview', ['mode' => 'reserve'])

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Remarks (Optional)</label>
                <input type="text" name="remarks" value="{{ old('remarks') }}" maxlength="500"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            </div>

            <div class="pt-2 flex items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    A reservation does not hold a physical copy and carries no fines or automatic expiry.
                    The librarian marks it Fulfilled or Cancelled manually.
                </p>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition whitespace-nowrap">
                    Reserve Book
                </button>
            </div>
        </form>
    </div>
@endsection
