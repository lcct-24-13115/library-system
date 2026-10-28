{{-- Policy/Services panel: default loan days and borrow limit per group. Saved by settings.policy.update. --}}
<div class="flex items-center justify-between mb-5">
    <h3 class="text-lg font-bold text-slate-900">Policy / Services</h3>
    <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">&larr; Back to Settings</a>
</div>

<form action="{{ route('settings.policy.update') }}" method="POST" class="space-y-5 max-w-2xl">
    @csrf
    @method('PUT')
    <input type="hidden" name="from" value="utilities">

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1" for="p-days">Default loan period (days)</label>
        <input id="p-days" type="number" name="loan_days" min="1" max="365" required value="{{ old('loan_days', $loanDays) }}"
               class="w-40 border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
        <p class="text-xs text-slate-500 mt-1">Pre-filled when checking out or renewing. The librarian can still change it per transaction.</p>
    </div>

    <div>
        <p class="text-sm font-semibold text-slate-700 mb-2">Books allowed on loan at one time, per group</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($limits as $type => $limit)
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">{{ $type }}</label>
                    <input type="number" name="max_loans[{{ $type }}]" min="1" max="50" required value="{{ old('max_loans.' . $type, $limit) }}"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-2">Overdue books still never block borrowing. Only this limit and the duplicate-title rule do.</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Save Policy</button>
        <a href="{{ route('utilities.index', ['tab' => 'settings']) }}" class="px-5 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Close</a>
    </div>
</form>
