{{-- Tab bar for the Utilities page. Plain links (?tab=...), so it works without JavaScript. --}}
<nav class="flex flex-wrap gap-1 -mb-px relative z-10" aria-label="Utilities tabs" role="tablist">
    @foreach($tabs as $key => $label)
        @php $active = $tab === $key; @endphp
        <a href="{{ route('utilities.index', ['tab' => $key]) }}"
           role="tab"
           @if($active) aria-current="page" aria-selected="true" @else aria-selected="false" @endif
           class="px-6 py-2.5 text-sm font-bold rounded-t-lg border border-b-0 transition
                  {{ $active ? 'bg-white text-indigo-700 border-slate-200' : 'bg-slate-100 text-slate-600 border-transparent hover:bg-slate-200' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
