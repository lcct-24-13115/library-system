<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'titles'         => Book::count(),
            'copies'         => (int) Book::sum('total_copies'),
            'available'      => (int) Book::sum('available_copies'),
            'patrons'        => Patron::where('status', 'active')->count(),
            'on_loan'        => Loan::active()->count(),
            'overdue'        => Loan::overdue()->count(),
            'issued_today'   => Loan::whereDate('issued_at', today())->count(),
            'returned_today' => Loan::whereDate('returned_at', today())->count(),
        ];

        // Last 7 days: books issued vs returned.
        $from = today()->subDays(6);

        $issued = Loan::selectRaw('DATE(issued_at) as d, COUNT(*) as c')
            ->whereDate('issued_at', '>=', $from)->groupBy('d')->pluck('c', 'd');
        $returned = Loan::selectRaw('DATE(returned_at) as d, COUNT(*) as c')
            ->whereNotNull('returned_at')->whereDate('returned_at', '>=', $from)->groupBy('d')->pluck('c', 'd');

        $chart = collect(range(0, 6))->map(function ($i) use ($from, $issued, $returned) {
            $day = $from->copy()->addDays($i);
            $key = $day->format('Y-m-d');

            return [
                'label'    => $day->format('D'),
                'date'     => $day->format('M d'),
                'issued'   => (int) ($issued[$key] ?? 0),
                'returned' => (int) ($returned[$key] ?? 0),
            ];
        });

        $chartMax = max(1, $chart->max(fn ($d) => max($d['issued'], $d['returned'])));

        $overdue = Loan::with(['book', 'patron'])->overdue()->orderBy('due_date')->take(5)->get();
        $recent  = Loan::with(['book', 'patron'])->latest('id')->take(8)->get();
        $popular = Book::withCount('loans')->whereHas('loans')->orderByDesc('loans_count')->take(5)->get();

        return view('dashboard', compact('stats', 'chart', 'chartMax', 'overdue', 'recent', 'popular'));
    }
}
