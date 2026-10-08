<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Circulation: Issue Book & Return Book.
 *
 * Business rules (per client):
 *  - NO automatic fines / penalties / receipts / generated files.
 *  - Overdue items never block a patron from borrowing.
 *  - Fines and payments are handled manually and offline by the librarian.
 *  - The only hard limit is physical: a book needs an available copy.
 */
class LoanController extends Controller
{
    /** Loan history with status filter + search + pagination. */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'all');

        $loans = Loan::with(['book', 'patron'])
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'returned', fn ($q) => $q->returned())
            ->when($search !== '', fn ($q) => $this->applySearch($q, $search))
            ->latest('issued_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'active'         => Loan::active()->count(),
            'overdue'        => Loan::overdue()->count(),
            'returned_today' => Loan::whereDate('returned_at', today())->count(),
        ];

        return view('loans.index', compact('loans', 'search', 'status', 'stats'));
    }

    /** Issue Book form. */
    public function create()
    {
        $recent = Loan::with(['book', 'patron'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('loans.create', [
            'recent'   => $recent,
            'loanDays' => config('library.loan_days', 7),
        ]);
    }

    /** Issue a book to a patron. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patron_id_number' => ['required', 'string', 'max:255'],
            'accession_number' => ['required', 'string', 'max:255'],
            'loan_days'        => ['required', 'integer', 'min:1', 'max:365'],
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
                'patron_id_number' => 'This patron is marked as inactive. Set the patron to active first if they may borrow.',
            ]);
        }

        $loan = DB::transaction(function () use ($data, $patron, $request) {
            // Lock the row so two desks can't issue the last copy at the same time.
            $book = Book::where('accession_number', trim($data['accession_number']))
                ->lockForUpdate()
                ->first();

            if (! $book) {
                throw ValidationException::withMessages([
                    'accession_number' => 'No book found with that accession number.',
                ]);
            }

            if ($book->available_copies < 1) {
                throw ValidationException::withMessages([
                    'accession_number' => "No copies of \"{$book->title}\" are currently available.",
                ]);
            }

            $book->decrement('available_copies');

            return Loan::create([
                'book_id'   => $book->id,
                'patron_id' => $patron->id,
                'issued_by' => $request->user()?->id,
                'issued_at' => now(),
                'due_date'  => today()->addDays((int) $data['loan_days']),
                'remarks'   => $data['remarks'] ?? null,
            ]);
        });

        $loan->load('book', 'patron');

        return redirect()
            ->route('loans.create')
            ->with('success', "Issued \"{$loan->book->title}\" to {$loan->patron->name}. Due on {$loan->due_date->format('M d, Y')}.");
    }

    /** Return Book screen: lists books currently on loan, searchable. */
    public function returnForm(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $loans = Loan::with(['book', 'patron'])
            ->active()
            ->when($search !== '', fn ($q) => $this->applySearch($q, $search))
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('loans.return', compact('loans', 'search'));
    }

    /** Mark a loan as returned and put the copy back on the shelf. */
    public function markReturned(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $loan = DB::transaction(function () use ($loan, $validated, $request) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'This book was already returned.',
                ]);
            }

            $book = Book::whereKey($loan->book_id)->lockForUpdate()->firstOrFail();

            $loan->update([
                'returned_at' => now(),
                'received_by' => $request->user()?->id,
                // Only overwrite remarks if the librarian typed something new.
                'remarks'     => filled($validated['remarks'] ?? null) ? $validated['remarks'] : $loan->remarks,
            ]);

            // Never exceed total copies (guards against manual catalog edits).
            if ($book->available_copies < $book->total_copies) {
                $book->increment('available_copies');
            }

            return $loan;
        });

        $loan->load('book', 'patron');

        $message = "Returned \"{$loan->book->title}\" from {$loan->patron->name}.";

        return back()->with('success', $message);
    }

    /** Search by book title/accession no. or patron name/ID number. */
    private function applySearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('book', function ($b) use ($search) {
                $b->where('title', 'like', "%{$search}%")
                  ->orWhere('accession_number', 'like', "%{$search}%");
            })->orWhereHas('patron', function ($p) use ($search) {
                $p->where('name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        });
    }
}
