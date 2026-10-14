@php $p = $patron ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ID Number</label>
        <input type="text" name="id_number" value="{{ old('id_number', $p?->id_number) }}" required placeholder="e.g. 2026-0001"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name</label>
        <input type="text" name="name" value="{{ old('name', $p?->name) }}" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Patron Type</label>
        <select name="patron_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            @foreach(\App\Models\Patron::TYPES as $type)
                <option value="{{ $type }}" @selected(old('patron_type', $p?->patron_type ?? 'Student') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
        <select name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            <option value="active" @selected(old('status', $p?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $p?->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email (Optional)</label>
        <input type="email" name="email" value="{{ old('email', $p?->email) }}"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Contact No. (Optional)</label>
        <input type="text" name="contact_no" value="{{ old('contact_no', $p?->contact_no) }}"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
    </div>
</div>
