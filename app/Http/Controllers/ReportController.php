<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * On-screen, printable reports (use the browser's Print button).
 *
 * Nothing is generated or stored as a file, and nothing here computes fines.
 * "Days overdue" is informational so the librarian can follow up manually.
 */
class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    /** Loan history for a date range (by date issued). */
    public function loanHistory(Request $request)
    {
        [$from, $to] = $this->range($request);
        $status = $request->input('status', 'all');

        $loans = Loan::with(['book', 'patron'])
            ->whereBetween('issued_at', [$from, $to])
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'returned', fn ($q) => $q->returned())
            ->orderBy('issued_at')
            ->limit(3000)
            ->get();

        return view('reports.loan-history', compact('loans', 'from', 'to', 'status'));
    }

    /** Overdue (default) or all books currently on loan. */
    public function overdue(Request $request)
    {
        $scope = $request->input('scope', 'overdue');

        $loans = Loan::with(['book', 'patron'])
            ->when($scope === 'overdue', fn ($q) => $q->overdue(), fn ($q) => $q->active())
            ->orderBy('due_date')
            ->get();

        return view('reports.overdue', compact('loans', 'scope'));
    }

    /** Totals, most borrowed titles, and breakdown by patron type / category. */
    public function summary(Request $request)
    {
        [$from, $to] = $this->range($request);

        $base = fn () => Loan::whereBetween('issued_at', [$from, $to]);

        $totals = [
            'issued'   => $base()->count(),
            'returned' => Loan::whereBetween('returned_at', [$from, $to])->count(),
            'renewals' => (int) Loan::whereBetween('last_renewed_at', [$from, $to])->count(),
            'patrons'  => $base()->distinct()->count('patron_id'),
        ];

        $topBooks = Book::withCount(['loans as borrow_count' => fn ($q) => $q->whereBetween('issued_at', [$from, $to])])
            ->whereHas('loans', fn ($q) => $q->whereBetween('issued_at', [$from, $to]))
            ->orderByDesc('borrow_count')
            ->orderBy('title')
            ->limit(20)
            ->get();

        $byPatronType = Loan::join('patrons', 'patrons.id', '=', 'loans.patron_id')
            ->whereBetween('loans.issued_at', [$from, $to])
            ->select('patrons.patron_type as label', DB::raw('COUNT(*) as total'))
            ->groupBy('patrons.patron_type')
            ->orderByDesc('total')
            ->get();

        $byCategory = Loan::join('books', 'books.id', '=', 'loans.book_id')
            ->whereBetween('loans.issued_at', [$from, $to])
            ->select(DB::raw("COALESCE(books.category, 'General') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw("COALESCE(books.category, 'General')"))
            ->orderByDesc('total')
            ->get();

        return view('reports.summary', compact('from', 'to', 'totals', 'topBooks', 'byPatronType', 'byCategory'));
    }

    /** Accession / shelf list with RDA call numbers. */
    public function accession(Request $request)
    {
        $filters = $request->only(['library', 'location', 'media_type', 'search']);

        $books = Book::query()
            ->when($filters['library'] ?? null, fn ($q, $v) => $q->where('library', $v))
            ->when($filters['location'] ?? null, fn ($q, $v) => $q->where('location', $v))
            ->when($filters['media_type'] ?? null, fn ($q, $v) => $q->where('media_type', $v))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('title', 'like', "%{$s}%")
                      ->orWhere('author', 'like', "%{$s}%")
                      ->orWhere('call_number', 'like', "%{$s}%")
                      ->orWhere('accession_number', 'like', "%{$s}%");
                });
            })
            ->orderBy('accession_number')
            ->limit(5000)
            ->get();

        return view('reports.accession', compact('books', 'filters'));
    }

    /** Parse ?from=&to= (defaults to the current month). */
    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : today()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : today()->endOfDay();

        return [$from, $to];
    }
}
