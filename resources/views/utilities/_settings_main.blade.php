{{-- Settings tab (main panel): excluded days, report heading, picture path, holidays / suspensions. --}}
@php
    $field = 'w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500';
    $editing = ! is_null($editHoliday ?? null);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- General settings --}}
    <div class="lg:col-span-2 border border-slate-200 rounded-xl p-5">
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="from" value="utilities">

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
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="s-heading">Report heading</label>
                <input id="s-heading" type="text" name="report_heading" maxlength="150" value="{{ old('report_heading', $heading) }}"
                       placeholder="LA CONSOLACION COLLEGE LIBRARY" class="{{ $field }}">
                <p class="text-xs text-slate-500 mt-1">Shown at the top of printed reports. Leave blank to use the default.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="s-picture">Picture path</label>
                <input id="s-picture" type="text" name="picture_path" maxlength="255" value="{{ old('picture_path', $picturePath) }}"
                       placeholder="D:\Pictures" class="{{ $field }}">
                <p class="text-xs text-slate-500 mt-1">Folder of the ID pictures (from the old system). Saved for reference only; ID photos are uploaded on each patron's page.</p>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Save Settings</button>
                <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Refresh</a>
            </div>
        </form>
    </div>

    {{-- Group / Policy setting --}}
    <div class="border border-slate-200 rounded-xl p-5">
        <p class="text-sm font-semibold text-rose-600 mb-3">Group / Policy setting</p>
        <div class="space-y-3">
            <a href="{{ route('utilities.index', ['tab' => 'settings', 'panel' => 'group']) }}" class="block text-center bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-3 rounded-lg shadow-sm transition">Group</a>
            <a href="{{ route('utilities.index', ['tab' => 'settings', 'panel' => 'policy']) }}" class="block text-center bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-3 rounded-lg shadow-sm transition">Policy/Services</a>
        </div>
        <p class="text-xs text-slate-500 mt-3">Patron groups, and the loan days / borrow limit for each.</p>
    </div>
</div>

{{-- Holidays / suspensions --}}
<div class="border border-slate-200 rounded-xl p-5 mt-6">
    <h3 class="text-lg font-bold text-slate-900 mb-1">Holiday / Suspension dates</h3>
    <p class="text-sm text-slate-500 mb-4">These dates are not counted as days overdue.</p>

    <form action="{{ $editing ? route('settings.holidays.update', $editHoliday) : route('settings.holidays.store') }}" method="POST"
          class="flex flex-col md:flex-row gap-3 md:items-end mb-5">
        @csrf
        @if($editing) @method('PUT') @endif
        <input type="hidden" name="from" value="utilities">
        <div>
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1" for="h-date">Date of holiday / suspension</label>
            <input id="h-date" type="date" name="date" required
                   value="{{ old('date', $editing ? \Illuminate\Support\Carbon::parse($editHoliday->date)->format('Y-m-d') : '') }}"
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex-1">
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1" for="h-reason">Reason (optional)</label>
            <input id="h-reason" type="text" name="reason" maxlength="150" value="{{ old('reason', $editHoliday->reason ?? '') }}"
                   placeholder="e.g. Christmas Day, class suspension" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="{{ $editing ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white font-semibold px-5 py-2 rounded-lg text-sm transition">{{ $editing ? 'Update' : 'Add' }}</button>
            @if($editing)
                <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Cancel</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-3">Holiday / Suspension</th>
                    <th class="p-3">Reason</th>
                    <th class="p-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($holidays as $h)
                    <tr class="{{ $editing && $editHoliday->is($h) ? 'bg-amber-50' : '' }}">
                        <td class="p-3 font-medium whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($h->date)->format('M d, Y (D)') }}</td>
                        <td class="p-3 text-slate-600">{{ $h->reason ?? '—' }}</td>
                        <td class="p-3 text-center space-x-1 whitespace-nowrap">
                            <a href="{{ route('utilities.index', ['tab' => 'settings', 'edit_holiday' => $h->id]) }}" class="inline-block bg-amber-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-amber-600 shadow-sm transition">Edit</a>
                            <form action="{{ route('settings.holidays.destroy', $h) }}" method="POST" class="inline-block" onsubmit="return confirm('Remove this date?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="from" value="utilities">
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

    <div class="mt-4 flex flex-wrap gap-2">
        @if($holidays->isNotEmpty())
            <form action="{{ route('settings.holidays.empty') }}" method="POST" onsubmit="return confirm('Remove ALL holiday / suspension dates?');">
                @csrf
                <input type="hidden" name="from" value="utilities">
                <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm transition">Empty list</button>
            </form>
        @endif
        <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Close</a>
    </div>
</div>
