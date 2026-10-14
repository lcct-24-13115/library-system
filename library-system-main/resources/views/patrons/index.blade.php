@extends('layouts.library')

@section('title', 'Patrons - SALRC Library')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Patrons</h2>
            <p class="text-slate-500 text-sm">Registered borrowers</p>
        </div>
        <a href="{{ route('patrons.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">+ Add New Patron</a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('patrons.index') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, ID number or type..."
                   class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">Search</button>
            @if($search !== '')
                <a href="{{ route('patrons.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4">ID Number</th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Type</th>
                    <th class="p-4">Contact</th>
                    <th class="p-4">Books Out</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($patrons as $patron)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-bold text-indigo-900">{{ $patron->id_number }}</td>
                        <td class="p-4 font-medium text-slate-800">{{ $patron->name }}</td>
                        <td class="p-4">
                            <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold">{{ $patron->patron_type }}</span>
                        </td>
                        <td class="p-4 text-slate-600">{{ $patron->email ?? $patron->contact_no ?? '—' }}</td>
                        <td class="p-4 font-semibold text-slate-700">{{ $patron->active_loans_count }}</td>
                        <td class="p-4">
                            @if($patron->isActive())
                                <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-semibold">Active</span>
                            @else
                                <span class="bg-slate-200 text-slate-600 px-2.5 py-1 rounded-full text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="p-4 text-center space-x-2 whitespace-nowrap">
                            <a href="{{ route('patrons.edit', $patron) }}" class="inline-block bg-amber-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-amber-600 shadow-sm transition">Edit</a>
                            <form action="{{ route('patrons.destroy', $patron) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this patron?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-rose-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-rose-700 shadow-sm transition">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-slate-400">
                            <p class="text-lg font-medium">No patrons found.</p>
                            <p class="text-sm mt-1">Add a patron before issuing books.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $patrons->links() }}</div>
@endsection
