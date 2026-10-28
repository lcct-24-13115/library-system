@php
    $user = auth()->user();

    // First matching route pattern wins, so specific routes sit above their wildcards.
    $crumbs = [
        'dashboard'         => 'Dashboard',
        'loans.create'      => 'Check Out',
        'loans.return.form' => 'Check In',
        'loans.*'           => 'Loans',
        'books.*'           => 'Books Catalog',
        'patrons.*'         => 'Patrons',
        'reservations.*'    => 'Reservations',
        'attendance.*'      => 'Attendance',
        'reports.*'         => 'Reports',
        'users.*'           => 'Users',
        'utilities.*'       => 'Utilities',
        'settings.*'        => 'Settings',
        'profile.*'         => 'My profile',
    ];
    $current = 'Library workspace';
    foreach ($crumbs as $pattern => $label) {
        if (request()->routeIs($pattern)) { $current = $label; break; }
    }

    $initials = '';
    if ($user) {
        foreach (array_slice(preg_split('/\s+/', trim($user->name)), 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }
@endphp

<header class="topbar print:hidden">
    <div class="crumb">
        <span class="crumb-root">SALRC</span>
        <span class="crumb-root"><x-icon name="chevron" :size="13" /></span>
        <strong>{{ $current }}</strong>
    </div>

    @auth
        <div class="top-actions">
            <a href="{{ route('profile.edit') }}" class="user-chip" title="Edit your profile">
                <span class="avatar">{{ $initials }}</span>
                <span class="who">{{ $user->name }}<small>{{ $user->role }}</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn"><x-icon name="logout" :size="14" /> Log out</button>
            </form>
        </div>
    @endauth
</header>
