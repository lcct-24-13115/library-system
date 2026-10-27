@extends('layouts.library')

@section('title', 'Import Books - SALRC Library')
@section('width', 'max-w-3xl')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900">Import Books from Excel</h2>
        <a href="{{ route('books.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">&larr; Back to Book Catalog</a>
    </div>

    @if($result = session('import_result'))
        <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg text-sm">
            <strong>{{ number_format($result['created']) }}</strong> of {{ number_format($result['total']) }} {{ \Illuminate\Support\Str::plural('row', $result['total']) }} added as new books.
            @if(count($result['skipped']))
                <strong>{{ number_format(count($result['skipped'])) }}</strong> skipped (see below).
            @endif
        </div>

        @if(count($result['skipped']))
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-6">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                        <tr><th class="p-3">Row</th><th class="p-3">Accession no.</th><th class="p-3">Title</th><th class="p-3">Why it was skipped</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach(array_slice($result['skipped'], 0, 100) as $s)
                            <tr>
                                <td class="p-3">{{ $s['row'] }}</td>
                                <td class="p-3">{{ $s['acc'] ?: '—' }}</td>
                                <td class="p-3">{{ $s['title'] ?: '—' }}</td>
                                <td class="p-3 text-amber-700">{{ $s['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($result['skipped']) > 100)
                    <p class="p-3 text-xs text-slate-500">Showing the first 100 of {{ number_format(count($result['skipped'])) }} skipped rows.</p>
                @endif
            </div>
        @endif
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">How it works</h3>
        <ol class="list-decimal ml-5 text-sm text-slate-700 space-y-1">
            <li>Download the <a href="{{ asset('templates/books-import-template.csv') }}" class="text-indigo-600 font-semibold underline" download>sample sheet</a> and open it in Excel.</li>
            <li>Fill in one book per row. Only <strong>accession_number</strong> and <strong>title</strong> are required. Other columns you can use: author, isbn, category, total_copies, available_copies, subtitle, publisher, year_of_publication, place_of_publication, edition_statement, call_number, subjects, genre, language, summary, location, library, price, date_acquired, dealer_donor, notes. Columns you do not need can be deleted; columns this system does not know are ignored.</li>
            <li>A blank <em>author</em> is saved as <em>Unknown</em>. Blank copies means 1 copy. Blank available copies means all copies are on the shelf.</li>
            <li>Save as <strong>Excel Workbook (.xlsx)</strong> or <strong>CSV</strong>, then upload it here.</li>
        </ol>
        <p class="text-xs text-slate-500 mt-3">An accession number that already exists is skipped and never changed. Up to {{ number_format(20000) }} rows and 10 MB per file. Tip: format the accession number and ISBN columns as <em>Text</em> in Excel so long numbers are not changed to something like 9.78E+12.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('books.import.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-3 md:items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Excel or CSV file</label>
                <input type="file" name="file" required accept=".xlsx,.csv,.txt" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                @error('file')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Import books</button>
        </form>
    </div>
@endsection
