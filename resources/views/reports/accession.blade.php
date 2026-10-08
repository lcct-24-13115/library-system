@extends('layouts.library')

@section('title', 'Accession List - SALRC Library')
@section('width', 'max-w-7xl')

@section('content')
    @include('reports._header', [
        'title' => 'Accession / Shelf List',
        'subtitle' => $books->count() . ' record(s)',
    ])

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 print:hidden">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, author, call no., accession no."
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            @foreach(['library' => ['Library', 'libraries'], 'location' => ['Location', 'locations'], 'media_type' => ['Media type', 'media_types']] as $field => [$label, $key])
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">{{ $label }}</label>
                    <select name="{{ $field }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All</option>
                        @foreach(config("rda.$key") as $opt)
                            <option value="{{ $opt }}" @selected(($filters[$field] ?? '') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="md:col-span-5 flex gap-3">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Generate</button>
                <button type="button" onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Print</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto print:shadow-none print:border-0">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">Accession No.</th>
                    <th class="p-3">Call No.</th>
                    <th class="p-3">Title</th>
                    <th class="p-3">Author</th>
                    <th class="p-3">Publisher</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Media</th>
                    <th class="p-3">Location</th>
                    <th class="p-3 text-right">Copies</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($books as $book)
                    <tr>
                        <td class="p-3 font-bold text-indigo-900">{{ $book->accession_number }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $book->call_number ?? '—' }}</td>
                        <td class="p-3">{{ $book->title }}@if($book->subtitle): {{ $book->subtitle }}@endif</td>
                        <td class="p-3">{{ $book->author }}</td>
                        <td class="p-3">{{ $book->publisher ?? '—' }}</td>
                        <td class="p-3">{{ $book->year_of_publication ?? '—' }}</td>
                        <td class="p-3">{{ $book->media_type ? ucfirst($book->media_type) : '—' }}</td>
                        <td class="p-3">{{ $book->location ?? '—' }}</td>
                        <td class="p-3 text-right">{{ $book->available_copies }} / {{ $book->total_copies }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-10 text-center text-slate-400">No books match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
