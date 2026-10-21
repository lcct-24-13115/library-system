{{-- One CSS-3D book: spine, cover (hinged flap), page edge, top/bottom, back, first page. --}}
<span class="book3d" aria-hidden="true">
    <span class="book3d__body">
        <span class="book3d__face book3d__back"></span>
        <span class="book3d__face book3d__bottom"></span>
        <span class="book3d__face book3d__top"></span>
        <span class="book3d__face book3d__pages"></span>
        <span class="book3d__face book3d__spine">
            <span class="spine-title">{{ \Illuminate\Support\Str::limit($book->title, 46) }}</span>
            <i class="spine-dot {{ $book->available_copies > 0 ? 'is-on' : 'is-off' }}"></i>
        </span>
        <span class="book3d__face book3d__block">
            <small>{{ $mode === 'staff' ? $book->accession_number : ($book->call_number ?: 'SALRC') }}</small>
            <b>{{ \Illuminate\Support\Str::limit($book->title, 60) }}</b>
            <small>{{ $book->available_copies }} / {{ $book->total_copies }} available</small>
        </span>
        <span class="book3d__cover">
            <span class="book3d__face book3d__cover-front">
                <span class="cv-cat">{{ $book->category ?: 'General' }}</span>
                <strong>{{ $book->title }}</strong>
                <em>{{ $book->author }}</em>
            </span>
            <span class="book3d__face book3d__cover-inner"></span>
        </span>
    </span>
</span>
