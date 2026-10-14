@php $editing = isset($user); $self = $editing && $user->is(auth()->user()); @endphp

<div>
    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
           class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
</div>

<div>
    <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
           class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">User Type <span class="text-rose-600">*</span></label>
        <select name="user_type" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <option value="">Select a user type...</option>
            @foreach(\App\Models\User::USER_TYPES as $type)
                <option value="{{ $type }}" @selected(old('user_type', $user->user_type ?? '') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Role <span class="text-rose-600">*</span></label>
        <select name="role" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <option value="">Select a role...</option>
            @foreach(\App\Models\User::ROLES as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
        @if($self)
            <p class="text-xs text-slate-500 mt-1">You cannot change your own role.</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Password @if($editing)<span class="text-slate-400 font-normal">(leave blank to keep)</span>@endif</label>
        <input type="password" name="password" autocomplete="new-password" @if(! $editing) required @endif
               class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm Password</label>
        <input type="password" name="password_confirmation" autocomplete="new-password" @if(! $editing) required @endif
               class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
</div>
