@extends('layouts.portal')

@section('title', 'Library Catalog - SALRC')

@section('content')
    <h1 class="text-3xl font-extrabold text-slate-900">Library Catalog</h1>
    <p class="text-slate-500 text-sm mb-6">Search the collection. No account needed.</p>

    <form action="{{ route('catalog.index') }}" method="GET" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row gap-3">
        <input type="search" name="q" value="{{ $q }}" placeholder="Title, author, subject, call number, ISBN..."
               class="flex-1 border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
        @if($libraries->isNotEmpty())
            <select name="library" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All libraries</option>
                @foreach($libraries as $lib)
                    <option value="{{ $lib }}" @selected($library === $lib)>{{ $lib }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm transition">Search</button>
    </form>

    <p class="text-sm text-slate-500 mb-3">{{ $books->total() }} {{ \Illuminate\Support\Str::plural('title', $books->total()) }} found</p>

    <div class="space-y-3">
        @forelse($books as $book)
            <article class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-900">{{ $book->title }}@if($book->subtitle): <span class="font-medium text-slate-600">{{ $book->subtitle }}</span>@endif</h2>
                    <p class="text-sm text-slate-600">{{ $book->author ?: '—' }}@if($book->publisher || $book->year_of_publication) &middot; {{ trim($book->publisher . ' ' . $book->year_of_publication) }}@endif</p>
                    <p class="text-xs text-slate-500 mt-1">
                        @if($book->call_number)Call no. <span class="font-semibold">{{ $book->call_number }}</span>@endif
                        @if($book->library) &middot; {{ $book->library }}@endif
                        @if($book->location) &middot; {{ $book->location }}@endif
                    </p>
                    @if($book->subjects)<p class="text-xs text-slate-400 mt-1">{{ $book->subjects }}</p>@endif
                </div>
                <div class="shrink-0">
                    @if($book->available_copies > 0)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Available: {{ $book->available_copies }} of {{ $book->total_copies }}</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">All copies on loan</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">No books found.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $books->links() }}</div>
@endsection
