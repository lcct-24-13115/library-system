{{-- Phase 4: remarks history. Old loans.remarks (read-only) + dated notes + void info. --}}
@if($loan->remarks)
    <div class="text-xs text-slate-600 whitespace-normal">{{ $loan->remarks }}</div>
@endif
@foreach($loan->notes as $note)
    <div class="text-xs text-slate-500 mt-0.5 whitespace-normal">
        <span class="text-slate-400">{{ $note->created_at->format('M d, Y h:i A') }} &middot; {{ $note->user?->name ?? 'Unknown user' }}:</span>
        {{ $note->note }}
    </div>
@endforeach
@if($loan->voided_at)
    <div class="text-xs text-slate-600 mt-0.5 whitespace-normal">
        <span class="font-semibold">Voided</span>
        {{ $loan->voided_at->format('M d, Y h:i A') }} &middot; {{ $loan->voidedBy?->name ?? 'Unknown user' }}: {{ $loan->void_reason }}
    </div>
@endif
@if(! $loan->remarks && $loan->notes->isEmpty() && ! $loan->voided_at)
    <span class="text-slate-300">&mdash;</span>
@endif
