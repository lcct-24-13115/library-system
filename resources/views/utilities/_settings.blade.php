{{-- Settings tab: the main panel, or the Group / Policy panel chosen with ?panel=group|policy. --}}
@if(($panel ?? null) === 'group')
    @include('utilities._settings_group')
@elseif(($panel ?? null) === 'policy')
    @include('utilities._settings_policy')
@else
    @include('utilities._settings_main')
@endif
