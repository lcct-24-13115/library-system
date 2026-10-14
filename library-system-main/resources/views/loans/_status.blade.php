@php $s = $loan->status; @endphp
@if($s === 'returned')
    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-semibold">Returned</span>
@elseif($s === 'overdue')
    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-semibold">
        Overdue {{ $loan->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $loan->daysOverdue()) }}
    </span>
@else
    <span class="bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full text-xs font-semibold">On loan</span>
@endif
