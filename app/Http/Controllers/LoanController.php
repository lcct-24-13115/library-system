<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Circulation: Check Out & Check In.
 *
 * Business rules (per client):
 *  - NO automatic fines / penalties / receipts / generated files.
 *  - Overdue items never block a patron from borrowing.
 *  - Fines and payments are handled manually and offline by the librarian.
 *  - Hard limits: a book needs an available copy; a patron may not exceed the
 *    per-type borrow limit (admin/librarian can override with a remark) and may not
 *    borrow the same title twice at once.
 */
class LoanController extends Controller
{
    /** Loan history with status filter + search + pagination. */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'all');

        $loans = Loan::with(['book', 'patron', 'notes.user', 'voidedBy'])
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'returned', fn ($q) => $q->returned())
            ->when($status === 'voided', fn ($q) => $q->voided())
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

    /** Check Out form. */
    public function create()
    {
        $recent = Loan::with(['book', 'patron'])
            ->latest('id')
            ->take(5)
            ->get();

        // Phase 5: books already issued to the current patron in this multi-book session.
        [$sessionPatron, $sessionLoans] = $this->issueSession();

        return view('loans.create', [
            'recent'        => $recent,
            'loanDays'      => app(\App\Support\LibrarySettings::class)->loanDays(),
            'sessionPatron' => $sessionPatron,
            'sessionLoans'  => $sessionLoans,
        ]);
    }

    /**
     * Phase 5: "Done" - the patron is finished, clear the multi-book session so the
     * next patron starts with an empty screen. Nothing is saved or changed in the database.
     */
    public function issueDone(Request $request)
    {
        $request->session()->forget(self::ISSUE_SESSION_KEY);

        return redirect()->route('loans.create');
    }

    /**
     * Phase 5: overdue list for offline follow-up by staff. Display only
     * (patron, book, due date, days overdue, contact). Print with the browser.
     * Voided and returned loans are not in it (Loan::overdue()). Overdue never blocks anything.
     */
    public function overdue()
    {
        $loans = Loan::overdue()
            ->with(['book', 'patron'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return view('loans.overdue', compact('loans'));
    }

    /** Issue a book to a patron. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patron_id_number' => ['required', 'string', 'max:255'],
            'accession_number' => ['required', 'string', 'max:255'],
            'loan_days'        => ['required', 'integer', 'min:1', 'max:365'],
            'remarks'          => ['nullable', 'string', 'max:500'],
            'override_limit'   => ['nullable', 'boolean'],
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
            // Lock the patron row so two desks can't push the same patron past the limit.
            Patron::whereKey($patron->id)->lockForUpdate()->first();

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

            // Same title already out with this patron? Block (no override).
            $alreadyHas = Loan::active()
                ->where('patron_id', $patron->id)
                ->where('book_id', $book->id)
                ->exists();

            if ($alreadyHas) {
                throw ValidationException::withMessages([
                    'accession_number' => "{$patron->name} already has \"{$book->title}\" on loan.",
                ]);
            }

            // Borrow limit (overdue items still never block; this is a shelf-protection limit).
            $limit  = $patron->maxLoans();
            $active = Loan::active()->where('patron_id', $patron->id)->count();

            if ($active >= $limit) {
                $user        = $request->user();
                $wantsBypass = $request->boolean('override_limit');
                $canBypass   = $user && $user->canManage();

                if (! ($wantsBypass && $canBypass)) {
                    $hint = $canBypass
                        ? ' Tick "Override limit" and add a remark to issue anyway.'
                        : ' Ask a librarian or admin to approve an override.';

                    throw ValidationException::withMessages([
                        'patron_id_number' => "{$patron->name} already has {$active} of {$limit} allowed books on loan.{$hint}",
                    ]);
                }

                if (! filled($data['remarks'] ?? null)) {
                    throw ValidationException::withMessages([
                        'remarks' => 'A remark is required when overriding the borrow limit.',
                    ]);
                }
            }

            $book->decrement('available_copies');

            $newLoan = Loan::create([
                'book_id'   => $book->id,
                'patron_id' => $patron->id,
                'issued_by' => $request->user()?->id,
                'issued_at' => now(),
                'due_date'  => today()->addDays((int) $data['loan_days']),
            ]);

            // Phase 4: the remark is kept as a dated note (with the user's name).
            $this->addNote($newLoan, $data['remarks'] ?? null, $request);

            return $newLoan;
        });

        $loan->load('book', 'patron');

        $this->rememberIssued($request, $loan);

        return redirect()
            ->route('loans.create')
            ->with('success', "Checked out \"{$loan->book->title}\" to {$loan->patron->name}. Due on {$loan->due_date->format('M d, Y')}.");
    }

    /** Phase 5: session key and how long an idle multi-book session is kept (minutes). */
    private const ISSUE_SESSION_KEY     = 'issue_session';
    private const ISSUE_SESSION_MINUTES = 30;

    /** Phase 5: keep the patron and add this loan to the "issued this session" list. */
    private function rememberIssued(Request $request, Loan $loan): void
    {
        $previous = $request->session()->get(self::ISSUE_SESSION_KEY);

        // A different patron starts a fresh list; the same patron keeps adding to it.
        $ids = (is_array($previous) && (int) ($previous['patron_id'] ?? 0) === (int) $loan->patron_id)
            ? ($previous['loan_ids'] ?? [])
            : [];

        $ids[] = $loan->id;

        $request->session()->put(self::ISSUE_SESSION_KEY, [
            'patron_id' => $loan->patron_id,
            'loan_ids'  => $ids,
            'at'        => now()->timestamp,
        ]);
    }

    /**
     * Phase 5: the patron and loans of the current multi-book session.
     * An idle session (no issue for ISSUE_SESSION_MINUTES) is dropped so a forgotten
     * patron ID never sits in the box for the next person.
     *
     * @return array{0: ?Patron, 1: \Illuminate\Support\Collection}
     */
    private function issueSession(): array
    {
        $data = session(self::ISSUE_SESSION_KEY);

        if (! is_array($data) || empty($data['loan_ids'])) {
            return [null, collect()];
        }

        if (now()->timestamp - (int) ($data['at'] ?? 0) > self::ISSUE_SESSION_MINUTES * 60) {
            session()->forget(self::ISSUE_SESSION_KEY);

            return [null, collect()];
        }

        $patron = Patron::find($data['patron_id'] ?? null);

        if (! $patron) {
            return [null, collect()];
        }

        $loans = Loan::with('book')
            ->whereIn('id', $data['loan_ids'])
            ->where('patron_id', $patron->id)
            ->orderBy('id')
            ->get();

        return [$patron, $loans];
    }

    /** Check In screen: lists books currently on loan, searchable. */
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

        // Handy for the scan screen: what was returned today (latest first).
        $recentReturns = Loan::returned()
            ->with(['book', 'patron'])
            ->whereDate('returned_at', today())
            ->latest('returned_at')
            ->latest('id')
            ->take(10)
            ->get();

        return view('loans.return', compact('loans', 'search', 'recentReturns'));
    }

    /** Mark a loan as returned and put the copy back on the shelf. */
    public function markReturned(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        [$loan, $daysLate] = $this->closeLoan($loan, $validated, $request);

        return back()->with($this->returnFlash($loan, $daysLate));
    }

    /**
     * Phase 3: the copy was lost. The loan is closed with outcome 'lost', the catalog
     * loses one copy (total_copies - 1) and available_copies is NOT increased.
     * A remark is required. No amount, no payment: replacement is settled offline.
     */
    public function markLost(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        [$loan, $daysLate] = $this->closeLoan($loan, $validated, $request, Loan::OUTCOME_LOST, false);

        return back()->with($this->returnFlash($loan, $daysLate, Loan::OUTCOME_LOST, false));
    }

    /**
     * Phase 3: the copy came back damaged. `restock` = 1 puts it back on the shelf
     * (same copy counts as a normal return); `restock` = 0 means it can no longer be
     * lent, so the counts change exactly like a lost copy. A remark is required.
     */
    public function markDamaged(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
            'restock' => ['required', 'in:0,1'],
        ]);

        $restock = $validated['restock'] === '1';

        [$loan, $daysLate] = $this->closeLoan($loan, $validated, $request, Loan::OUTCOME_DAMAGED, $restock);

        return back()->with($this->returnFlash($loan, $daysLate, Loan::OUTCOME_DAMAGED, $restock));
    }

    /**
     * Phase 4: void a loan that was issued by mistake (admin / librarian only).
     * Only a loan that is still out can be voided. The copy goes back on the shelf
     * (never above total_copies) and we record who voided it and why. The loan row
     * is NEVER deleted: it stays in the Loan History with a "Voided" label, and it no
     * longer counts toward the borrow limit or the active / overdue lists.
     */
    public function void(Request $request, Loan $loan)
    {
        abort_unless($request->user()?->canManage(), 403, 'You do not have permission to do that.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $loan = DB::transaction(function () use ($loan, $data, $request) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->isVoided()) {
                throw ValidationException::withMessages([
                    'loan' => 'This loan was already voided.',
                ]);
            }

            if ($loan->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'Only a loan that is still out can be voided. This one is already closed.',
                ]);
            }

            $book = Book::whereKey($loan->book_id)->lockForUpdate()->firstOrFail();

            $loan->update([
                'voided_at'   => now(),
                'voided_by'   => $request->user()->id,
                'void_reason' => trim($data['reason']),
            ]);

            // The copy never really left: put it back, never above total copies.
            if ($book->available_copies < $book->total_copies) {
                $book->increment('available_copies');
            }

            return $loan;
        });

        $loan->load('book', 'patron');

        return back()->with('success', "Voided the loan of \"{$loan->book->title}\" to {$loan->patron->name}. The copy is back on the shelf.");
    }

    /**
     * Scan-first return: the librarian scans the book's accession number and the
     * active loan for that book is returned right away.
     *
     * If the same accession is out with more than one patron (several copies),
     * we never guess: we show the matching loans so staff can pick the right one.
     * Overdue never blocks a return; it only adds an "collect offline" notice.
     */
    public function scanReturn(Request $request)
    {
        $data = $request->validate([
            'accession_number' => ['required', 'string', 'max:255'],
            'remarks'          => ['nullable', 'string', 'max:500'],
        ]);

        $accession = trim($data['accession_number']);
        $book      = Book::where('accession_number', $accession)->first();

        if (! $book) {
            throw ValidationException::withMessages([
                'accession_number' => 'No book found with that accession number.',
            ]);
        }

        $loans = Loan::active()
            ->where('book_id', $book->id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        if ($loans->isEmpty()) {
            throw ValidationException::withMessages([
                'accession_number' => "\"{$book->title}\" has no active loan, so there is nothing to check in.",
            ]);
        }

        if ($loans->count() > 1) {
            return redirect()
                ->route('loans.return.form', ['search' => $accession])
                ->with('error', "More than one patron has \"{$book->title}\" on loan. Pick the right one below and click Check In.");
        }

        [$loan, $daysLate] = $this->closeLoan($loans->first(), $data, $request);

        return redirect()
            ->route('loans.return.form')
            ->with($this->returnFlash($loan, $daysLate));
    }

    /**
     * Shared by every way of closing a loan (return, scan, lost, damaged).
     * $restock = true  -> the copy goes back on the shelf (available_copies + 1).
     * $restock = false -> the copy leaves the collection (total_copies - 1, available
     *                     is not increased). Neither count can go below zero.
     *
     * @return array{0: Loan, 1: int} the closed loan and how many days late it was
     */
    private function closeLoan(
        Loan $loan,
        array $validated,
        Request $request,
        string $outcome = Loan::OUTCOME_RETURNED,
        bool $restock = true
    ): array {
        $daysLate = 0;

        $loan = DB::transaction(function () use ($loan, $validated, $request, $outcome, $restock, &$daysLate) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->isVoided()) {
                throw ValidationException::withMessages([
                    'loan' => 'This loan was voided, so it cannot be checked in.',
                ]);
            }

            if ($loan->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'This book was already checked in.',
                ]);
            }

            $book = Book::whereKey($loan->book_id)->lockForUpdate()->firstOrFail();

            $daysLate = $loan->daysOverdue();

            $loan->update([
                'returned_at' => now(),
                'outcome'     => $outcome,
                'received_by' => $request->user()?->id,
            ]);

            // Phase 4: a typed remark is added as a dated note; earlier remarks are never overwritten.
            $text = trim((string) ($validated['remarks'] ?? ''));
            if ($text !== '') {
                $this->addNote($loan, $outcome === Loan::OUTCOME_RETURNED ? $text : strtoupper($outcome) . ': ' . $text, $request);
            }

            if ($restock) {
                // Never exceed total copies (guards against manual catalog edits).
                if ($book->available_copies < $book->total_copies) {
                    $book->increment('available_copies');
                }
            } else {
                // The copy is gone: one fewer in the collection, never below zero,
                // and available can never be higher than the new total.
                $newTotal = max(0, (int) $book->total_copies - 1);
                $book->total_copies     = $newTotal;
                $book->available_copies = max(0, min((int) $book->available_copies, $newTotal));
                $book->save();
            }

            return $loan;
        });

        $loan->load('book', 'patron');

        return [$loan, $daysLate];
    }

    /** Phase 4: add one dated note (with the user) to a loan. Blank text adds nothing. */
    private function addNote(Loan $loan, ?string $text, Request $request): void
    {
        $text = trim((string) $text);

        if ($text === '') {
            return;
        }

        $loan->notes()->create([
            'user_id' => $request->user()?->id,
            'note'    => $text,
        ]);
    }

    /** Flash messages shown after a return. The notices are information only. */
    private function returnFlash(Loan $loan, int $daysLate, string $outcome = Loan::OUTCOME_RETURNED, bool $restocked = true): array
    {
        $title  = $loan->book->title;
        $patron = $loan->patron->name;

        $flash = [
            'success' => match (true) {
                $outcome === Loan::OUTCOME_LOST
                    => "Marked \"{$title}\" ({$patron}) as LOST. One copy was removed from the collection. Replacement is settled offline.",
                $outcome === Loan::OUTCOME_DAMAGED && $restocked
                    => "Marked \"{$title}\" ({$patron}) as DAMAGED. The copy is back on the shelf.",
                $outcome === Loan::OUTCOME_DAMAGED
                    => "Marked \"{$title}\" ({$patron}) as DAMAGED and withdrawn. One copy was removed from the collection.",
                default
                    => "Checked in \"{$title}\" from {$patron}.",
            },
        ];

        if ($daysLate > 0) {
            $flash['overdue_notice'] = "{$daysLate} " . Str::plural('day', $daysLate)
                . ' overdue. Collect any fine offline.';
        }

        // No copy came back to the shelf, so there is nothing to hold aside.
        if (! $restocked) {
            return $flash;
        }

        $next = $loan->book->reservations()
            ->pending()
            ->with('patron')
            ->orderBy('reserved_at')
            ->orderBy('id')
            ->first();

        if ($next) {
            $flash['reservation_notice'] = "Reserved for {$next->patron->name} (first in queue). Hold this copy aside.";
        }

        return $flash;
    }

    /**
     * Renew (extend) a loan that is still out.
     * New due date = later of (current due date, today) + the days entered.
     * Renewals are never blocked by overdue status or fines; only an
     * optional cap (config library.max_renewals) can stop them.
     */
    public function renew(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'days'    => ['required', 'integer', 'min:1', 'max:365'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $loan = DB::transaction(function () use ($loan, $validated, $request) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->isVoided()) {
                throw ValidationException::withMessages([
                    'loan' => 'This loan was voided and cannot be renewed.',
                ]);
            }

            if ($loan->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'This book was already checked in and cannot be renewed.',
                ]);
            }

            $max = config('library.max_renewals');

            if ($max !== null && $loan->renewal_count >= $max) {
                throw ValidationException::withMessages([
                    'loan' => "This loan has reached the maximum of {$max} renewals.",
                ]);
            }

            $base = $loan->due_date->gt(today()) ? $loan->due_date->copy() : today();

            $loan->update([
                'original_due_date' => $loan->original_due_date ?? $loan->due_date,
                'due_date'          => $base->addDays((int) $validated['days']),
                'renewal_count'     => $loan->renewal_count + 1,
                'last_renewed_at'   => now(),
            ]);

            $this->addNote($loan, $validated['remarks'] ?? null, $request);

            return $loan;
        });

        $loan->load('book', 'patron');

        return back()->with('success', "Renewed \"{$loan->book->title}\" for {$loan->patron->name}. New due date: {$loan->due_date->format('M d, Y')}.");
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
