@extends('layouts.library')

@section('title', 'Users - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Users</h2>
            <p class="text-slate-500 text-sm">Library system accounts (admin only). “Online” = active in the last 5 minutes. Accounts are deactivated, never deleted.</p>
        </div>
        <a href="{{ route('users.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">+ Add New User</a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('users.index') }}" method="GET" class="flex gap-2">
            <select name="user_type" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                <option value="">All user types</option>
                @foreach(\App\Models\User::USER_TYPES as $type)
                    <option value="{{ $type }}" @selected($userType === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Filter</button>
            @if($userType !== '')
                <a href="{{ route('users.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                    <th class="p-4">User Type</th>
                    <th class="p-4">Access Right</th>
                    <th class="p-4">Library</th>
                    <th class="p-4">Current User</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($users as $u)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-medium text-slate-800">{{ $u->name }}</td>
                        <td class="p-4 text-slate-600">{{ $u->email }}</td>
                        <td class="p-4">{{ $u->user_type ?? '—' }}</td>
                        <td class="p-4 capitalize">{{ $u->role }}</td>
                        <td class="p-4">{{ $u->libraryLabel() }}</td>
                        <td class="p-4">
                            @if(in_array($u->id, $onlineIds, true) || $u->is(auth()->user()))
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">Online</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if($u->is_active)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Inactive</span>
                            @endif
                        </td>
                        <td class="p-4 text-center space-x-2 whitespace-nowrap">
                            <a href="{{ route('users.edit', $u) }}" class="inline-block bg-amber-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-amber-600 shadow-sm transition">Edit</a>
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
                    <tr><td colspan="8" class="p-8 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
