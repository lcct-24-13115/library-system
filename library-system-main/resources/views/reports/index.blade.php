@extends('layouts.library')

@section('title', 'Reports - SALRC Library')

@section('content')
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-slate-900">Reports</h2>
        <p class="text-slate-500 text-sm">View on screen, then click Print to print or save as PDF from your browser.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach([
            [route('reports.loan-history'), 'Loan History', 'Every borrowing transaction in a date range, filterable by status.'],
            [route('reports.overdue'), 'Overdue & On-loan List', 'Who still has what, with contact details for manual follow-up. No fines are computed.'],
            [route('reports.summary'), 'Circulation Summary', 'Totals, most borrowed titles, and loans by patron type and category.'],
            [route('reports.accession'), 'Accession / Shelf List', 'Catalog listing with RDA call numbers, media type, library and location.'],
        ] as [$href, $name, $desc])
            <a href="{{ $href }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:border-indigo-300 hover:shadow transition">
                <h3 class="text-lg font-bold text-indigo-900">{{ $name }}</h3>
                <p class="text-sm text-slate-600 mt-1">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
@endsection
