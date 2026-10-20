@extends('layouts.library')

@section('title', 'Settings - SALRC Library')
@section('width', 'max-w-5xl')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Settings</h2>
            <p class="text-slate-500 text-sm">Admin only. These only change what is shown (days overdue, report heading) and the loan policy. No fines are computed.</p>
        </div>
        <a href="{{ route('settings.policy') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">Group / Policy</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <p class="text-sm font-semibold text-rose-600 mb-2">Check any day to exclude from the “days overdue” count.</p>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach($days as $num => $label)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="excluded_days[]" value="{{ $num }}"
                                   @checked(in_array($num, old('excluded_days', $excluded), false)) class="rounded border-slate-300">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Report heading</label>
                <input type="text" name="report_heading" maxlength="150" value="{{ old('report_heading', $heading) }}"
                       placeholder="LA CONSOLACION COLLEGE LIBRARY"
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Shown at the top of printed reports. Leave blank to use the default.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Holiday / Suspension dates</h3>
        <p class="text-sm text-slate-500 mb-4">These dates are not counted as days overdue.</p>

        <form action="{{ route('settings.holidays.store') }}" method="POST" class="flex flex-col md:flex-row gap-3 md:items-end mb-5">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Date</label>
                <input type="date" name="date" value="{{ old('date') }}" required class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Reason (optional)</label>
                <input type="text" name="reason" maxlength="150" value="{{ old('reason') }}" placeholder="e.g. Christmas Day, class suspension"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Add</button>
        </form>

        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="p-3">Date</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($holidays as $h)
                        <tr>
                            <td class="p-3 font-medium whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($h->date)->format('M d, Y (D)') }}</td>
                            <td class="p-3 text-slate-600">{{ $h->reason ?? '—' }}</td>
                            <td class="p-3 text-center">
                                <form action="{{ route('settings.holidays.destroy', $h) }}" method="POST" class="inline-block" onsubmit="return confirm('Remove this date?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-rose-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-rose-700 shadow-sm transition">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-6 text-center text-slate-400">No holiday / suspension dates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($holidays->isNotEmpty())
            <form action="{{ route('settings.holidays.empty') }}" method="POST" class="mt-4" onsubmit="return confirm('Remove ALL holiday / suspension dates?');">
                @csrf
                <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm transition">Empty list</button>
            </form>
        @endif
    </div>
@endsection
