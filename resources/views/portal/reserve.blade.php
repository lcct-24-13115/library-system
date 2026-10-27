@extends('layouts.portal')

@section('title', 'Reserve a Book - SALRC')

@section('content')
    <a href="{{ route('catalog.index') }}" class="text-sm text-indigo-700 hover:text-indigo-900">&larr; Back to catalog</a>
    <h1 class="text-3xl font-extrabold text-slate-900 mt-2 mb-1">Reserve a Book</h1>
    <p class="text-slate-500 text-sm mb-6">Your request goes to the librarian. You still need to come to the library with your ID to borrow the book.</p>

    @if($errors->has('reserve'))
        <div class="mb-6 p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-lg">{{ $errors->first('reserve') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-xl">
        <h2 class="font-bold text-slate-900 text-lg">{{ $book->title }}@if($book->subtitle): <span class="font-medium text-slate-600">{{ $book->subtitle }}</span>@endif</h2>
        <p class="text-sm text-slate-600">{{ $book->author ?: '—' }}</p>
        <p class="text-xs text-slate-500 mt-1">@if($book->call_number)Call no. <span class="font-semibold">{{ $book->call_number }}</span>@endif @if($book->library) &middot; {{ $book->library }}@endif</p>

        <div class="mt-6">
            @if($alreadyOnLoan)
                <p class="text-sm text-amber-700">You already have this book on loan.</p>
            @elseif($alreadyReserved)
                <p class="text-sm text-amber-700">You already have a pending request for this book.</p>
            @else
                <form action="{{ route('portal.reserve.store', $book) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm transition">Send request</button>
                </form>
            @endif
        </div>
    </div>
@endsection
