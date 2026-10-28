{{-- 3D bookshelf. Variables: $books (iterable of Book), $mode ('staff' | 'public').
     Pure CSS 3D (see public/css/salrc-3d.css); public/js/salrc-3d.js opens the modal. --}}
@php
    $palette = [
        ['#1e3a8a', '#3b82f6'], ['#0f2557', '#2563eb'], ['#0369a1', '#38bdf8'], ['#312e81', '#6366f1'],
        ['#134e4a', '#14b8a6'], ['#7c2d12', '#f59e0b'], ['#4c1d95', '#8b5cf6'], ['#334155', '#64748b'],
    ];
    $canManage = $mode === 'staff' && auth()->check() && auth()->user()->canManage();
@endphp

<div class="shelf3d" data-shelf>
    @forelse($books as $book)
        @php
            $seed = crc32($book->title . '|' . $book->accession_number);
            [$c1, $c2] = $palette[$seed % count($palette)];

            // Thickness from the page count in "extent" (e.g. "xii, 320 pages"); default 220 pages.
            preg_match_all('/\d+/', (string) $book->extent, $found);
            $pages = collect($found[0])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 20 && $n <= 2500)->max() ?: 220;
            $thickness = (int) max(32, min(68, 26 + $pages / 9));
            $height = 170 + ($seed % 5) * 8;

            $actions = [];
            if ($mode === 'staff') {
                $actions[] = ['label' => 'View details', 'url' => route('books.show', $book->id)];
                $actions[] = ['label' => 'Reserve', 'url' => route('reservations.create', ['accession_number' => $book->accession_number]), 'ghost' => true];
                if ($canManage) {
                    $actions[] = ['label' => 'Check Out', 'url' => route('loans.create', ['accession_number' => $book->accession_number]), 'ghost' => true];
                }
            } elseif (! auth()->check()) {
                $actions[] = ['label' => 'Sign in to borrow', 'url' => route('login')];
            } elseif (auth()->user()->isPatron()) {
                $actions[] = ['label' => 'Reserve', 'url' => route('portal.reserve.confirm', $book->id)];
            }

            $payload = [
                'title'     => $book->title,
                'subtitle'  => $book->subtitle,
                'author'    => $book->author,
                'category'  => $book->category,
                'call'      => $book->call_number,
                'accession' => $mode === 'staff' ? $book->accession_number : null,
                'publisher' => $book->publisher,
                'year'      => $book->year_of_publication,
                'available' => (int) $book->available_copies,
                'total'     => (int) $book->total_copies,
                'summary'   => \Illuminate\Support\Str::limit((string) $book->summary, 240),
                'actions'   => $actions,
            ];
        @endphp
        <div class="shelf3d__cell" style="--t:{{ $thickness }}px;--h:{{ $height }}px;--c1:{{ $c1 }};--c2:{{ $c2 }}">
            <button type="button" class="book3d-btn" aria-label="Open {{ $book->title }}"
                    data-book="{{ json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                @include('books._book3d', ['book' => $book, 'mode' => $mode])
            </button>
        </div>
    @empty
        <p class="shelf3d__empty">No books found.</p>
    @endforelse
</div>

@once
    @include('books._book-modal')
@endonce
