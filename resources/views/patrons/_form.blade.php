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

<div>
    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ID Photo (Optional)</label>
    <div class="flex items-center gap-4">
        @if($p?->photoUrl())
            <img src="{{ $p->photoUrl() }}" alt="Current photo of {{ $p->name }}" class="w-16 h-16 rounded-full object-cover border border-slate-200">
        @endif
        <div class="flex-1">
            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp"
                   class="w-full text-sm text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold hover:file:bg-slate-200">
            <p class="text-xs text-slate-400 mt-1">JPG, PNG or WEBP, max 2 MB. Shown on the attendance screen to confirm identity.</p>
            @if($p?->photo)
                <label class="inline-flex items-center gap-2 text-xs text-slate-600 mt-2">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300"> Remove current photo
                </label>
            @endif
        </div>
    </div>
</div>
