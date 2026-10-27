<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Student / Faculty portal. Each person sees only their own records. No fines or amounts.
 * A patron may REQUEST (reserve) a book and cancel their own pending request.
 * The librarian still issues the book at the desk; a reservation never holds a copy or blocks anything.
 */
class PortalController extends Controller
{
    public function index(Request $request)
    {
        $patron = $request->user()->patron;
        abort_if(! $patron, 403, 'This account is not linked to a patron record.');

        $active = Loan::with('book')->where('patron_id', $patron->id)->active()->orderBy('due_date')->get();

        $history = Loan::with('book')->where('patron_id', $patron->id)
            ->returned()->whereNull('voided_at')
            ->latest('returned_at')->limit(20)->get();

        $reservations = Reservation::with('book')->where('patron_id', $patron->id)
            ->where('status', Reservation::PENDING)->latest('reserved_at')->get();

        return view('portal.index', compact('patron', 'active', 'history', 'reservations'));
    }

    /** Confirm page before the student sends a reserve request. */
    public function reserveConfirm(Request $request, Book $book)
    {
        $patron = $this->patronOrFail($request);

        $alreadyOnLoan = Loan::active()->where('book_id', $book->id)->where('patron_id', $patron->id)->exists();
        $alreadyReserved = Reservation::pending()->where('book_id', $book->id)->where('patron_id', $patron->id)->exists();

        return view('portal.reserve', compact('patron', 'book', 'alreadyOnLoan', 'alreadyReserved'));
    }

    /** Student sends a reserve request. Shows up as Pending in the librarian's Reservations list. */
    public function reserve(Request $request, Book $book)
    {
        $patron = $this->patronOrFail($request);

        if (! $patron->isActive()) {
            return back()->withErrors(['reserve' => 'Your library card is inactive. Please see the librarian.']);
        }

        $result = DB::transaction(function () use ($book, $patron, $request) {
            $locked = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();

            if (Loan::active()->where('book_id', $locked->id)->where('patron_id', $patron->id)->exists()) {
                return ['error' => "You already have \"{$locked->title}\" on loan."];
            }

            if (Reservation::pending()->where('book_id', $locked->id)->where('patron_id', $patron->id)->exists()) {
                return ['error' => "You already have a pending request for \"{$locked->title}\"."];
            }

            $reservation = Reservation::create([
                'book_id'     => $locked->id,
                'patron_id'   => $patron->id,
                'status'      => Reservation::PENDING,
                'reserved_at' => now(),
                'reserved_by' => $request->user()->id,
                'remarks'     => 'Requested online by the patron.',
            ]);

            return ['reservation' => $reservation, 'book' => $locked];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['reserve' => $result['error']]);
        }

        $position = Reservation::queuePositions(collect([$result['reservation']]))[$result['reservation']->id] ?? 1;
        $note = $result['book']->available_copies > 0
            ? ' A copy is on the shelf. Please go to the library and show your ID so the librarian can issue it.'
            : ' All copies are on loan right now. The librarian will set it aside when it is returned.';

        return redirect()->route('portal.index')
            ->with('success', "Request sent for \"{$result['book']->title}\" (queue position #{$position}).{$note}");
    }

    /** A patron may cancel only their OWN pending request. */
    public function cancelReservation(Request $request, Reservation $reservation)
    {
        $patron = $this->patronOrFail($request);

        abort_unless($reservation->patron_id === $patron->id, 403);

        if (! $reservation->isPending()) {
            return back()->withErrors(['reserve' => "This request is already {$reservation->status}."]);
        }

        $reservation->update([
            'status'       => Reservation::CANCELLED,
            'cancelled_at' => now(),
            'handled_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Your request was cancelled.');
    }

    private function patronOrFail(Request $request)
    {
        $patron = $request->user()->patron;
        abort_if(! $patron, 403, 'This account is not linked to a patron record.');

        return $patron;
    }
}
