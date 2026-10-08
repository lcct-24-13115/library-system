@extends('layouts.library')

@section('title', $book->title . ' - SALRC Library')
@section('width', 'max-w-5xl')

@section('content')
    @php
        $rows = fn (array $pairs) => collect($pairs)->filter(fn ($v) => filled($v));
    @endphp

    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $book->accession_number }}</p>
            <h2 class="text-3xl font-extrabold text-slate-900">{{ $book->title }}@if($book->subtitle)<span class="text-slate-500 font-semibold">: {{ $book->subtitle }}</span>@endif</h2>
            <p class="text-slate-600 mt-1">{{ $book->statement_of_responsibility ?: $book->author }}</p>
        </div>
        <div class="flex gap-2 print:hidden">
            <a href="{{ route('books.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">&larr; Catalog</a>
            <a href="{{ route('books.edit', $book->id) }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 shadow-sm">Edit</a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900 shadow-sm">Print</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            @foreach([
                'Bibliographic Description (RDA)' => [
                    'Author / Creator' => $book->author,
                    'Edition' => $book->edition_statement,
                    'Place of publication' => $book->place_of_publication,
                    'Publisher' => $book->publisher,
                    'Date of publication' => $book->year_of_publication,
                    'Series' => $book->series_statement,
                    'ISBN' => $book->isbn,
                ],
                'Content, Media & Carrier (RDA)' => [
                    'Content type' => $book->content_type,
                    'Media type' => $book->media_type,
                    'Carrier type' => $book->carrier_type,
                    'Extent' => $book->extent,
                    'Dimensions' => $book->dimensions,
                    'Language' => $book->language,
                ],
                'Classification' => [
                    'Call number' => $book->call_number,
                    'Category' => $book->category,
                    'Genre / form' => $book->genre,
                ],
            ] as $heading => $pairs)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">{{ $heading }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-y-2 text-sm">
                        @forelse($rows($pairs) as $label => $value)
                            <dt class="text-slate-500 font-semibold">{{ $label }}</dt>
                            <dd class="sm:col-span-2 text-slate-800">{{ $value }}</dd>
                        @empty
                            <dd class="sm:col-span-3 text-slate-400">No data yet.</dd>
                        @endforelse
                    </dl>
                </div>
            @endforeach

            @if($book->subjects || $book->summary || $book->notes)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 text-sm space-y-3">
                    @if($book->subjects)
                        <div>
                            <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider mb-2">Subjects</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($book->subjectList() as $subject)
                                    <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold">{{ $subject }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($book->summary)
                        <div>
                            <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider mb-1">Summary</h3>
                            <p class="text-slate-700 whitespace-pre-line">{{ $book->summary }}</p>
                        </div>
                    @endif
                    @if($book->notes)
                        <div>
                            <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider mb-1">Notes</h3>
                            <p class="text-slate-700">{{ $book->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">Availability</h3>
                <p class="text-3xl font-extrabold {{ $book->available_copies > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $book->available_copies }} <span class="text-base text-slate-500 font-semibold">of {{ $book->total_copies }} available</span>
                </p>
                <dl class="grid grid-cols-3 gap-y-2 text-sm mt-4">
                    @foreach($rows([
                        'Library' => $book->library,
                        'Location' => $book->location,
                        'Acquired' => $book->date_acquired?->format('M d, Y'),
                        'Dealer / Donor' => $book->dealer_donor,
                        'Price' => $book->price !== null ? '₱' . number_format($book->price, 2) : null,
                    ]) as $label => $value)
                        <dt class="text-slate-500 font-semibold">{{ $label }}</dt>
                        <dd class="col-span-2 text-slate-800">{{ $value }}</dd>
                    @endforeach
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">Recent Borrowing</h3>
                <ul class="space-y-3 text-sm">
                    @forelse($loans as $loan)
                        <li>
                            <div class="font-medium text-slate-800">{{ $loan->patron->name }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $loan->issued_at->format('M d, Y') }} &rarr; {{ $loan->returned_at?->format('M d, Y') ?? 'due ' . $loan->due_date->format('M d, Y') }}
                            </div>
                            <div class="mt-1">@include('loans._status', ['loan' => $loan])</div>
                        </li>
                    @empty
                        <li class="text-slate-400">Never borrowed.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
