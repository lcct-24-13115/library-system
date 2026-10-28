{{-- Group panel (read only): the patron groups, how many patrons each has, and the policy that applies. --}}
<div class="flex items-center justify-between mb-5">
    <h3 class="text-lg font-bold text-slate-900">Groups</h3>
    <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">&larr; Back to Settings</a>
</div>

<div class="overflow-x-auto border border-slate-200 rounded-lg">
    <table class="w-full text-left border-collapse text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
            <tr>
                <th class="p-3">Group</th>
                <th class="p-3 text-right">Patrons</th>
                <th class="p-3 text-right">Active</th>
                <th class="p-3 text-right">Loan days</th>
                <th class="p-3 text-right">Books allowed</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($groups as $g)
                <tr>
                    <td class="p-3 font-medium text-slate-800">{{ $g['type'] }}</td>
                    <td class="p-3 text-right">{{ $g['total'] }}</td>
                    <td class="p-3 text-right">{{ $g['active'] }}</td>
                    <td class="p-3 text-right">{{ $g['loan_days'] }}</td>
                    <td class="p-3 text-right">{{ $g['max_loans'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="text-xs text-slate-500 mt-3">
    The four groups are fixed because patron forms, imports and the borrow limit depend on them.
    To change the loan days or how many books a group may borrow, use Policy/Services.
</p>

<div class="mt-4 flex flex-wrap gap-3">
    <a href="{{ route('utilities.index', ['tab' => 'settings', 'panel' => 'policy']) }}" class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 shadow-sm transition">Policy/Services</a>
    <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Close</a>
</div>
