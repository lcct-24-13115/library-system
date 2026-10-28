{{-- Administration tab: add a new user, or edit the one chosen from Users List (?edit={id}). --}}
@php
    $editing = ! is_null($editUser ?? null);
    $self    = $editing && $editUser->is(auth()->user());
    $field   = 'w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500';
@endphp

<div class="max-w-2xl mx-auto">
    <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $editing ? 'Edit user: ' . $editUser->name : 'New user' }}</h3>
    <p class="text-sm text-slate-500 mb-5">
        @if($editing)
            Leave the password blank to keep the current one.
        @else
            To edit an existing user, open <strong>Users List</strong> and click Edit.
        @endif
    </p>

    <form action="{{ $editing ? route('users.update', $editUser) : route('users.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif
        <input type="hidden" name="from" value="utilities">

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-name">User Name <span class="text-rose-600">*</span></label>
            <input id="u-name" type="text" name="name" value="{{ old('name', $editUser->name ?? '') }}" required class="{{ $field }}">
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-email">Email <span class="text-rose-600">*</span></label>
            <input id="u-email" type="email" name="email" value="{{ old('email', $editUser->email ?? '') }}" required class="{{ $field }}">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-pass">Password @unless($editing)<span class="text-rose-600">*</span>@endunless</label>
                <input id="u-pass" type="password" name="password" autocomplete="new-password" @unless($editing) required @endunless class="{{ $field }}">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-pass2">Confirm Password</label>
                <input id="u-pass2" type="password" name="password_confirmation" autocomplete="new-password" @unless($editing) required @endunless class="{{ $field }}">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-role">Access Right <span class="text-rose-600">*</span></label>
                @if($self)
                    {{-- Your own role cannot be changed: show it, and send it unchanged. --}}
                    <input type="hidden" name="role" value="{{ $editUser->role }}">
                    <select id="u-role" disabled class="{{ $field }} bg-slate-100">
                        <option>{{ $roleLabels[$editUser->role] ?? ucfirst($editUser->role) }}</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">You cannot change your own role.</p>
                @else
                    <select id="u-role" name="role" required class="{{ $field }}">
                        <option value="">Select access right...</option>
                        @foreach($roleLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $editUser->role ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-type">User Type <span class="text-rose-600">*</span></label>
                <select id="u-type" name="user_type" required class="{{ $field }}">
                    <option value="">Select a user type...</option>
                    @foreach(\App\Models\User::USER_TYPES as $type)
                        <option value="{{ $type }}" @selected(old('user_type', $editUser->user_type ?? '') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1" for="u-lib">Library</label>
            <select id="u-lib" name="library" class="{{ $field }}">
                <option value="">All libraries</option>
                @foreach(\App\Support\Libraries::names() as $lib)
                    <option value="{{ $lib }}" @selected(old('library', $editUser->library ?? '') === $lib)>{{ $lib }}</option>
                @endforeach
            </select>
        </div>

        <div class="pt-4 flex flex-wrap justify-center gap-3">
            <a href="{{ route('utilities.index', ['tab' => 'administration']) }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">New</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">{{ $editing ? 'Update User' : 'Save User' }}</button>
            <a href="{{ route('dashboard') }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Close</a>
        </div>
    </form>
</div>
