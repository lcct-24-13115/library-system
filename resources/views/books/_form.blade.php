@php
    $b = $book ?? null;
    $in = 'w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500';
    $lbl = 'block text-xs font-bold uppercase text-slate-600 mb-1';
    $val = fn ($f) => old($f, $b?->{$f});
@endphp

{{-- ===================== BASIC INFORMATION ===================== --}}
<h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2">Basic Information</h3>

<div>
    <label class="{{ $lbl }}">Accession Number</label>
    <input type="text" name="accession_number" value="{{ $val('accession_number') }}" required placeholder="e.g. ACC-2026-001" class="{{ $in }}">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Title Proper</label>
        <input type="text" name="title" value="{{ $val('title') }}" required placeholder="e.g. Introduction to Information Technology" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Other Title Information (Subtitle)</label>
        <input type="text" name="subtitle" value="{{ $val('subtitle') }}" class="{{ $in }}">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Author / Creator</label>
        <input type="text" name="author" value="{{ $val('author') }}" required placeholder="Author name" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Statement of Responsibility</label>
        <input type="text" name="statement_of_responsibility" value="{{ $val('statement_of_responsibility') }}" placeholder="e.g. by Elizabeth Dowsett" class="{{ $in }}">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="{{ $lbl }}">ISBN (Optional)</label>
        <input type="text" name="isbn" value="{{ $val('isbn') }}" placeholder="978-3-16-148410-0" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Category</label>
        <input type="text" name="category" value="{{ $val('category') }}" placeholder="e.g. Technology, Science, Fiction" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Total Copies</label>
        <input type="number" name="total_copies" min="1" value="{{ old('total_copies', $b?->total_copies ?? 1) }}" required class="{{ $in }}">
    </div>
</div>

@if($b)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="{{ $lbl }}">Available Copies</label>
            <input type="number" name="available_copies" min="0" value="{{ old('available_copies', $b->available_copies) }}" required class="{{ $in }}">
            <p class="text-xs text-slate-400 mt-1">Adjusted automatically when books are issued or returned.</p>
        </div>
    </div>
@endif

{{-- ===================== PUBLICATION (RDA) ===================== --}}
<h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 pt-4">Publication (RDA)</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Edition Statement</label>
        <input type="text" name="edition_statement" value="{{ $val('edition_statement') }}" placeholder="e.g. 2nd edition" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Series Statement</label>
        <input type="text" name="series_statement" value="{{ $val('series_statement') }}" class="{{ $in }}">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="{{ $lbl }}">Place of Publication</label>
        <input type="text" name="place_of_publication" value="{{ $val('place_of_publication') }}" placeholder="e.g. New York" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Publisher</label>
        <input type="text" name="publisher" value="{{ $val('publisher') }}" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Date of Publication</label>
        <input type="text" name="year_of_publication" value="{{ $val('year_of_publication') }}" placeholder="e.g. 2011 or c2011" class="{{ $in }}">
    </div>
</div>

{{-- ===================== CONTENT / MEDIA / CARRIER (RDA) ===================== --}}
<h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 pt-4">Content, Media &amp; Carrier (RDA)</h3>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach([
        'content_type' => ['Content Type', 'content_types'],
        'media_type'   => ['Media Type', 'media_types'],
        'carrier_type' => ['Carrier Type', 'carrier_types'],
    ] as $field => [$label, $configKey])
        <div>
            <label class="{{ $lbl }}">{{ $label }}</label>
            <select name="{{ $field }}" class="{{ $in }}">
                <option value="">— Select —</option>
                @foreach(config("rda.$configKey") as $opt)
                    <option value="{{ $opt }}" @selected($val($field) === $opt)>{{ ucfirst($opt) }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="{{ $lbl }}">Extent</label>
        <input type="text" name="extent" value="{{ $val('extent') }}" placeholder="e.g. 95 pages" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Dimensions</label>
        <input type="text" name="dimensions" value="{{ $val('dimensions') }}" placeholder="e.g. 31 cm" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Language</label>
        <select name="language" class="{{ $in }}">
            <option value="">— Select —</option>
            @foreach(config('rda.languages') as $opt)
                <option value="{{ $opt }}" @selected($val('language') === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- ===================== CLASSIFICATION & SUBJECTS ===================== --}}
<h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 pt-4">Classification &amp; Subjects</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Call Number</label>
        <input type="text" name="call_number" value="{{ $val('call_number') }}" placeholder="e.g. 688.725 D767l" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Genre / Form</label>
        <select name="genre" class="{{ $in }}">
            <option value="">— Select —</option>
            @foreach(config('rda.genres') as $opt)
                <option value="{{ $opt }}" @selected($val('genre') === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label class="{{ $lbl }}">Subjects</label>
    <input type="text" name="subjects" value="{{ $val('subjects') }}" placeholder="Separate with semicolons, e.g. Models and modelmaking; LEGO toys" class="{{ $in }}">
</div>

<div>
    <label class="{{ $lbl }}">Summary</label>
    <textarea name="summary" rows="3" class="{{ $in }}">{{ $val('summary') }}</textarea>
</div>

<div>
    <label class="{{ $lbl }}">Notes</label>
    <input type="text" name="notes" value="{{ $val('notes') }}" list="rda-notes" placeholder="e.g. Includes index" class="{{ $in }}">
    <datalist id="rda-notes">
        @foreach(config('rda.notes') as $opt)
            <option value="{{ $opt }}">
        @endforeach
    </datalist>
</div>

{{-- ===================== LOCATION & ACQUISITION ===================== --}}
<h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider border-b border-slate-200 pb-2 pt-4">Location &amp; Acquisition</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Library</label>
        <select name="library" class="{{ $in }}">
            <option value="">— Select —</option>
            @foreach(config('rda.libraries') as $opt)
                <option value="{{ $opt }}" @selected($val('library') === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $lbl }}">Location / Collection</label>
        <select name="location" class="{{ $in }}">
            <option value="">— Select —</option>
            @foreach(config('rda.locations') as $opt)
                <option value="{{ $opt }}" @selected($val('location') === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="{{ $lbl }}">Date Acquired</label>
        <input type="date" name="date_acquired" value="{{ old('date_acquired', $b?->date_acquired?->format('Y-m-d')) }}" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Dealer / Donor</label>
        <input type="text" name="dealer_donor" value="{{ $val('dealer_donor') }}" class="{{ $in }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Price</label>
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $b?->price) }}" class="{{ $in }}">
    </div>
</div>
