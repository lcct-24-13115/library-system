{{-- Date-range filter (hidden when printing) --}}
<div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 print:hidden">
    <form method="GET" class="flex flex-col md:flex-row md:items-end gap-3">
        <div>
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">From</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">To</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        @if(!empty($withStatus))
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
                <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['all' => 'All', 'active' => 'On loan', 'overdue' => 'Overdue', 'returned' => 'Returned'] as $value => $label)
                        <option value="{{ $value }}" @selected(($status ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Generate</button>
        <button type="button" onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Print</button>
    </form>
</div>
