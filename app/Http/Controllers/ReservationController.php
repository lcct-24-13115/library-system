<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reservations (holds).
 *
 * - Librarians reserve a book for a patron (the book may be on loan or on the shelf).
 * - Basic manual statuses: Pending -> Fulfilled / Cancelled.
 * - No automated fines/penalties/expiry. A reservation does not hold a copy and
 *   never blocks issuing, returning or renewing.
 */
class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'pending');

        if (! in_array($status, ['all', ...Reservation::STATUSES], true)) {
            $status = 'pending';
        }

        $reservations = Reservation::with(['book', 'patron'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->whereHas('book', function ($b) use ($search) {
                        $b->where('title', 'like', "%{$search}%")
                          ->orWhere('accession_number', 'like', "%{$search}%");
                    })->orWhereHas('patron', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('id_number', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status === 'pending', fn ($q) => $q->orderBy('reserved_at'), fn ($q) => $q->latest('reserved_at'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $positions = Reservation::queuePositions($reservations->getCollection());

        $counts = [
            'pending'   => Reservation::where('status', Reservation::PENDING)->count(),
            'fulfilled' => Reservation::where('status', Reservation::FULFILLED)->count(),
            'cancelled' => Reservation::where('status', Reservation::CANCELLED)->count(),
        ];

        return view('reservations.index', compact('reservations', 'positions', 'counts', 'search', 'status'));
    }

    /** Reserve form (accepts ?patron_id_number= and ?accession_number= to prefill). */
    public function create()
    {
        return view('reservations.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patron_id_number' => ['required', 'string', 'max:255'],
            'accession_number' => ['required', 'string', 'max:255'],
            'remarks'          => ['nullable', 'string', 'max:500'],
        ]);

        $patron = Patron::where('id_number', trim($data['patron_id_number']))->first();

        if (! $patron) {
            throw ValidationException::withMessages([
                'patron_id_number' => 'No patron found with that ID number. Register the patron first.',
            ]);
        }

        if (! $patron->isActive()) {
            throw ValidationException::withMessages([
                'patron_id_number' => 'This patron is marked as inactive. Set the patron to active first if they may reserve books.',
            ]);
        }

        $reservation = DB::transaction(function () use ($data, $patron, $request) {
            // Lock the book row so two desks can't create the same reservation at once.
            $book = Book::where('accession_number', trim($data['accession_number']))
                ->lockForUpdate()
                ->first();

            if (! $book) {
                throw ValidationException::withMessages([
                    'accession_number' => 'No book found with that accession number.',
                ]);
            }

            if (Loan::active()->where('book_id', $book->id)->where('patron_id', $patron->id)->exists()) {
                throw ValidationException::withMessages([
                    'accession_number' => "{$patron->name} already has \"{$book->title}\" on loan.",
                ]);
            }

            if (Reservation::pending()->where('book_id', $book->id)->where('patron_id', $patron->id)->exists()) {
                throw ValidationException::withMessages([
                    'accession_number' => "{$patron->name} already has a pending reservation for \"{$book->title}\".",
                ]);
            }

            return Reservation::create([
                'book_id'     => $book->id,
                'patron_id'   => $patron->id,
                'status'      => Reservation::PENDING,
                'reserved_at' => now(),
                'reserved_by' => $request->user()?->id,
                'remarks'     => $data['remarks'] ?? null,
            ]);
        });

        $reservation->load('book', 'patron');

        $position = Reservation::queuePositions(collect([$reservation]))[$reservation->id] ?? 1;

        $note = $reservation->book->available_copies > 0
            ? ' A copy is on the shelf right now - it can be issued directly.'
            : '';

        return redirect()
            ->route('reservations.index')
            ->with('success', "Reserved \"{$reservation->book->title}\" for {$reservation->patron->name} (queue position #{$position}).{$note}");
    }

    /** Mark a pending reservation as fulfilled (done manually by the librarian). */
    public function fulfill(Request $request, Reservation $reservation)
    {
        $reservation = $this->transition($reservation, Reservation::FULFILLED, 'fulfilled_at', $request);

        return back()->with('success', "Reservation for \"{$reservation->book->title}\" ({$reservation->patron->name}) marked as fulfilled.");
    }

    /** Cancel a pending reservation. */
    public function cancel(Request $request, Reservation $reservation)
    {
        $reservation = $this->transition($reservation, Reservation::CANCELLED, 'cancelled_at', $request);

        return back()->with('success', "Reservation for \"{$reservation->book->title}\" ({$reservation->patron->name}) cancelled.");
    }

    private function transition(Reservation $reservation, string $to, string $stampColumn, Request $request): Reservation
    {
        $reservation = DB::transaction(function () use ($reservation, $to, $stampColumn, $request) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'reservation' => "This reservation is already {$locked->status}.",
                ]);
            }

            $locked->update([
                'status'      => $to,
                $stampColumn  => now(),
                'handled_by'  => $request->user()?->id,
            ]);

            return $locked;
        });

        return $reservation->load('book', 'patron');
    }
}
