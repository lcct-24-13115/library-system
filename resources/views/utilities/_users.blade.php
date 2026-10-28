{{-- Users List tab. "Delete" in the old system = Deactivate here (accounts are never deleted). --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
    <form action="{{ route('utilities.index') }}" method="GET" class="flex gap-2">
        <input type="hidden" name="tab" value="users">
        <select name="user_type" class="border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <option value="">All user types</option>
            @foreach(\App\Models\User::USER_TYPES as $type)
                <option value="{{ $type }}" @selected($userType === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Filter</button>
    </form>

    <div class="flex gap-2">
        <a href="{{ request()->fullUrl() }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Refresh</a>
        <a href="{{ route('dashboard') }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Close</a>
    </div>
</div>

<p class="text-xs text-slate-500 mb-3">“Current User” = active in the last 5 minutes. Accounts are deactivated, never deleted.</p>

<div class="overflow-x-auto border border-slate-200 rounded-lg">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
            <tr>
                <th class="p-3">User name</th>
                <th class="p-3">Current User</th>
                <th class="p-3">Access right</th>
                <th class="p-3">Library</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @forelse($users as $u)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-3">
                        <div class="font-medium text-slate-800">{{ $u->name }}</div>
                        <div class="text-xs text-slate-500">{{ $u->email }}</div>
                    </td>
                    <td class="p-3">
                        @if(in_array($u->id, $onlineIds, true) || $u->is(auth()->user()))
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">Online</span>
                        @endif
                    </td>
                    <td class="p-3">{{ $roleLabels[$u->role] ?? ucfirst($u->role) }}</td>
                    <td class="p-3">{{ $u->libraryLabel() }}</td>
                    <td class="p-3">
                        @if($u->is_active)
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Inactive</span>
                        @endif
                    </td>
                    <td class="p-3 text-center space-x-1 whitespace-nowrap">
                        <a href="{{ route('utilities.index', ['tab' => 'administration', 'edit' => $u->id]) }}" class="inline-block bg-amber-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-amber-600 shadow-sm transition">Edit</a>
                        <form action="{{ route('users.reset-password', $u) }}" method="POST" class="inline-block" onsubmit="return confirm('Reset this user\'s password to a new temporary one?');">
                            @csrf
                            <button type="submit" class="bg-slate-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-slate-800 shadow-sm transition">Reset password</button>
                        </form>
                        @if($u->is_active && ! $u->is(auth()->user()))
                            <form action="{{ route('users.deactivate', $u) }}" method="POST" class="inline-block" onsubmit="return confirm('Deactivate this user? They will no longer be able to log in.');">
                                @csrf
                                <button type="submit" class="bg-rose-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-rose-700 shadow-sm transition">Deactivate</button>
                            </form>
                        @elseif(! $u->is_active)
                            <form action="{{ route('users.activate', $u) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="bg-emerald-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-emerald-700 shadow-sm transition">Reactivate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center text-slate-500">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
