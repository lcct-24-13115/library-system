{{-- Title block: visible on screen and at the top of the printout --}}
<div class="mb-4">
    <h2 class="text-2xl font-extrabold text-slate-900">{{ $title }}</h2>
    @isset($subtitle)
        <p class="text-slate-600 text-sm">{{ $subtitle }}</p>
    @endisset
    <p class="text-slate-400 text-xs hidden print:block">SALRC Library System &middot; Printed {{ now()->format('M d, Y h:i A') }}</p>
</div>
