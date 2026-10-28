@php
    $user = auth()->user();

    $groups = [
        [null, [
            ['Dashboard', 'dashboard', route('dashboard'), request()->routeIs('dashboard')],
        ]],
        ['Circulation', [
            ['Check Out', 'issue', route('loans.create'), request()->routeIs('loans.create')],
            ['Check In', 'return', route('loans.return.form'), request()->routeIs('loans.return.form')],
            ['Loans', 'loans', route('loans.index'), request()->routeIs('loans.index')],
            ['Reservations', 'bookmark', route('reservations.index'), request()->routeIs('reservations.*')],
        ]],
        ['Collection', [
            ['Books Catalog', 'books', route('books.index'), request()->routeIs('books.*')],
            ['Patrons', 'users', route('patrons.index'), request()->routeIs('patrons.*')],
        ]],
        ['Library', [
            ['Visitor Log', 'attendance', route('attendance.history'), request()->routeIs('attendance.stats', 'attendance.history')],
            ['Reports', 'reports', route('reports.index'), request()->routeIs('reports.*')],
            ['Web Search', 'globe', '#web-search', false],
        ]],
    ];

    if ($user && $user->isAdmin()) {
        $groups[] = ['Admin', [
            ['Utilities', 'utilities', route('utilities.index'), request()->routeIs('utilities.*', 'users.*', 'settings.*')],


        ]];
    }
@endphp

<aside class="sidebar print:hidden">
    <a href="{{ route('dashboard') }}" class="brand" aria-label="SALRC Library System home">
        <img src="{{ asset('images/salrc-logo-full.png') }}" alt="Saint Augustine Learning Resource Center" style="width:56px;height:56px;object-fit:contain;flex-shrink:0;">
        <div>
            <div class="brand-title">SALRC</div>
            <div class="brand-sub">Library System</div>
        </div>
    </a>

    <nav aria-label="Primary navigation">
        @foreach($groups as [$label, $items])
            <section>
                @if($label)
                    <div class="nav-label">{{ $label }}</div>
                @endif
                @foreach($items as [$text, $icon, $href, $active])
                    @if($href === '#web-search')
                        {{-- Opens the Web Search modal (partials/web-search.blade.php) --}}
                        <button type="button" class="nav-link ws-trigger" data-websearch-open aria-haspopup="dialog">
                            <x-icon :name="$icon" :size="16" />{{ $text }}
                        </button>
                        @continue
                    @endif
                    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                        <x-icon :name="$icon" :size="16" />{{ $text }}
                    </a>
                @endforeach
            </section>
        @endforeach
    </nav>

    <div class="sidebar-foot">
        Saint Augustine<br>Learning Resource Center
    </div>
</aside>
