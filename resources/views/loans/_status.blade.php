@php $s = $loan->status; @endphp
@if($s === 'voided')
    <span class="bg-slate-200 text-slate-600 px-2.5 py-1 rounded-full text-xs font-semibold">Voided</span>
@elseif($s === 'returned' && $loan->outcome === 'lost')
    <span class="bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full text-xs font-semibold">Lost</span>
@elseif($s === 'returned' && $loan->outcome === 'damaged')
    <span class="bg-orange-100 text-orange-800 px-2.5 py-1 rounded-full text-xs font-semibold">Damaged</span>
@elseif($s === 'returned')
    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-semibold">Returned</span>
@elseif($s === 'overdue')
    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-semibold">
        Overdue {{ $loan->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $loan->daysOverdue()) }}
    </span>
@else
    <span class="bg-indigo-100 text-indigo-800 px-2.5 py-1 rounded-full text-xs font-semibold">On loan</span>
@endif
