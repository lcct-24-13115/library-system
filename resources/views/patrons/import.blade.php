@extends('layouts.library')

@section('title', 'Import Patrons - SALRC Library')
@section('width', 'max-w-3xl')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900">Import Patrons from Excel</h2>
        <a href="{{ route('patrons.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">&larr; Back to Patrons</a>
    </div>

    @if($result = session('import_result'))
        <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg text-sm">
            <strong>{{ $result['created'] }}</strong> of {{ $result['total'] }} {{ \Illuminate\Support\Str::plural('row', $result['total']) }} added as new patrons.
            @if(count($result['skipped']))
                <strong>{{ count($result['skipped']) }}</strong> skipped (see below).
            @endif
            Students can sign in right away with their ID number as username and password.
        </div>

        @if(count($result['skipped']))
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-6">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                        <tr><th class="p-3">Row</th><th class="p-3">ID number</th><th class="p-3">Name</th><th class="p-3">Why it was skipped</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach(array_slice($result['skipped'], 0, 100) as $s)
                            <tr>
                                <td class="p-3">{{ $s['row'] }}</td>
                                <td class="p-3">{{ $s['id'] ?: '—' }}</td>
                                <td class="p-3">{{ $s['name'] ?: '—' }}</td>
                                <td class="p-3 text-amber-700">{{ $s['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($result['skipped']) > 100)
                    <p class="p-3 text-xs text-slate-500">Showing the first 100 of {{ count($result['skipped']) }} skipped rows.</p>
                @endif
            </div>
        @endif
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 mb-3">How it works</h3>
        <ol class="list-decimal ml-5 text-sm text-slate-700 space-y-1">
            <li>Download the <a href="{{ asset('templates/patrons-import-template.csv') }}" class="text-indigo-600 font-semibold underline" download>sample sheet</a> and open it in Excel.</li>
            <li>Fill in one student per row. Columns: <strong>id_number</strong>, <strong>name</strong>, patron_type (Student / Faculty / Staff / Outside User), email, contact_no. Only ID number and name are required; a blank type becomes <em>Student</em>. Other columns in your sheet (like degree or year level) are ignored. Headings like <em>studnum</em> and <em>studname</em> are understood too.</li>
            <li>Save as <strong>Excel Workbook (.xlsx)</strong> or <strong>CSV</strong>, then upload it here.</li>
        </ol>
        <p class="text-xs text-slate-500 mt-3">An ID number that already exists is skipped and never changed. Up to 5,000 rows per file. Tip: format the ID column as <em>Text</em> in Excel so IDs like 2026-0001 are not turned into dates.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('patrons.import.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-3 md:items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Excel or CSV file</label>
                <input type="file" name="file" required accept=".xlsx,.csv,.txt" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Import patrons</button>
        </form>
    </div>
@endsection
