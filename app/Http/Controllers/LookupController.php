<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only JSON lookups that power the live preview on the Check Out
 * (and Reserve) forms. Uses the same exact-match rules as the store actions,
 * so what the librarian sees is what will be matched on submit.
 *
 * Informational only: nothing here blocks anyone (overdue items, etc.).
 */
class LookupController extends Controller
{
    public function patron(Request $request): JsonResponse
    {
        $idNumber = trim((string) $request->query('id_number'));

        $patron = $idNumber === '' ? null : Patron::where('id_number', $idNumber)->first();

        if (! $patron) {
            return response()->json(['found' => false]);
        }

        $active = $patron->loans()->active()->with('book')->orderBy('due_date')->get();

        return response()->json([
            'found'                     => true,
            'id_number'                 => $patron->id_number,
            'name'                      => $patron->name,
            'patron_type'               => $patron->patron_type,
            'status'                    => $patron->status,
            'is_active'                 => $patron->isActive(),
            'email'                     => $patron->email,
            'contact_no'                => $patron->contact_no,
            'profile_url'               => route('patrons.show', $patron),
            'active_loans_count'        => $active->count(),
            'max_loans'                 => $patron->maxLoans(),
            'overdue_count'             => $active->filter(fn ($l) => $l->isOverdue())->count(),
            'pending_reservations_count' => $patron->reservations()->pending()->count(),
            'loans'                     => $active->take(5)->map(fn ($l) => [
                'title'            => $l->book->title,
                'accession_number' => $l->book->accession_number,
                'due_date'         => $l->due_date->format('M d, Y'),
                'overdue'          => $l->isOverdue(),
                'days_overdue'     => $l->daysOverdue(),
            ])->values(),
        ]);
    }

    public function book(Request $request): JsonResponse
    {
        $accession = trim((string) $request->query('accession_number'));

        $book = $accession === '' ? null : Book::where('accession_number', $accession)->first();

        if (! $book) {
            return response()->json(['found' => false]);
        }

        $pending = $book->reservations()
            ->pending()
            ->with('patron')
            ->orderBy('reserved_at')
            ->orderBy('id')
            ->get();

        // Optional: relate the book to the patron currently typed in the form.
        $patronNo = trim((string) $request->query('patron_id_number'));
        $patron   = $patronNo === '' ? null : Patron::where('id_number', $patronNo)->first();

        $position = null;
        $hasLoan  = false;

        if ($patron) {
            $index    = $pending->search(fn ($r) => (int) $r->patron_id === (int) $patron->id);
            $position = $index === false ? null : $index + 1;
            $hasLoan  = Loan::active()->where('book_id', $book->id)->where('patron_id', $patron->id)->exists();
        }

        $next = $pending->first();

        return response()->json([
            'found'                      => true,
            'accession_number'           => $book->accession_number,
            'title'                      => $book->title,
            'subtitle'                   => $book->subtitle,
            'author'                     => $book->author,
            'isbn'                       => $book->isbn,
            'call_number'                => $book->call_number,
            'library'                    => $book->library,
            'location'                   => $book->location,
            'available_copies'           => (int) $book->available_copies,
            'total_copies'               => (int) $book->total_copies,
            'is_available'               => $book->available_copies > 0,
            'show_url'                   => route('books.show', $book->id),
            'pending_reservations_count' => $pending->count(),
            'next_in_queue'              => $next ? ['name' => $next->patron->name, 'id_number' => $next->patron->id_number] : null,
            'patron_reservation_position' => $position,
            'patron_has_loan'            => $hasLoan,
        ]);
    }
}
