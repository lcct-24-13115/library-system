@extends('layouts.library')

@section('title', 'Circulation Summary - SALRC Library')

@section('content')
    @include('reports._header', [
        'title' => 'Circulation Summary',
        'subtitle' => $from->format('M d, Y') . ' – ' . $to->format('M d, Y'),
    ])

    @include('reports._range')

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach(['Books issued' => $totals['issued'], 'Books returned' => $totals['returned'], 'Renewals' => $totals['renewals'], 'Distinct borrowers' => $totals['patrons']] as $label => $value)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 print:shadow-none">
                <p class="text-xs uppercase font-bold text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-extrabold text-indigo-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        @foreach(['Loans by patron type' => $byPatronType, 'Loans by category' => $byCategory] as $heading => $rows)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 print:shadow-none">
                <h3 class="font-bold text-slate-900 mb-3">{{ $heading }}</h3>
                @php $max = max(1, $rows->max('total') ?? 1); @endphp
                <ul class="space-y-2 text-sm">
                    @forelse($rows as $row)
                        <li>
                            <div class="flex justify-between"><span>{{ $row->label }}</span><span class="font-semibold">{{ $row->total }}</span></div>
                            <div class="h-2 bg-slate-100 rounded"><div class="h-2 bg-indigo-500 rounded" style="width: {{ $row->total / $max * 100 }}%"></div></div>
                        </li>
                    @empty
                        <li class="text-slate-400">No data for this period.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto print:shadow-none">
        <h3 class="font-bold text-slate-900 p-4 pb-0">Most borrowed titles</h3>
        <table class="w-full text-left border-collapse text-sm mt-2">
            <thead class="bg-slate-50 border-y border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Accession No.</th>
                    <th class="p-3">Title</th>
                    <th class="p-3">Author</th>
                    <th class="p-3">Call No.</th>
                    <th class="p-3 text-right">Times borrowed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($topBooks as $i => $book)
                    <tr>
                        <td class="p-3 text-slate-400">{{ $i + 1 }}</td>
                        <td class="p-3 font-bold text-indigo-900">{{ $book->accession_number }}</td>
                        <td class="p-3">{{ $book->title }}</td>
                        <td class="p-3">{{ $book->author }}</td>
                        <td class="p-3">{{ $book->call_number ?? '—' }}</td>
                        <td class="p-3 text-right font-bold">{{ $book->borrow_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-400">No borrowing in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
