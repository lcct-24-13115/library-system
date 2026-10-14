@php $s = $reservation->status; @endphp
@if($s === 'fulfilled')
    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-semibold">Fulfilled</span>
@elseif($s === 'cancelled')
    <span class="bg-slate-200 text-slate-600 px-2.5 py-1 rounded-full text-xs font-semibold">Cancelled</span>
@else
    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-semibold">Pending</span>
@endif
