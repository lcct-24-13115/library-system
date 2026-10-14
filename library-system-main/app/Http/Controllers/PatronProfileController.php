<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use App\Models\Reservation;

/**
 * Complete borrower profile (read-only view):
 * contact details, active loans, borrowing history, attendance history, reservations.
 *
 * Informational only - no fines are calculated or shown.
 */
class PatronProfileController extends Controller
{
    public function show(Patron $patron)
    {
        $activeLoans = $patron->loans()
            ->active()
            ->with('book')
            ->orderBy('due_date')
            ->get();

        $history = $patron->loans()
            ->returned()
            ->with('book')
            ->latest('returned_at')
            ->latest('id')
            ->take(15)
            ->get();

        $visits = $patron->attendances()
            ->latest('visited_at')
            ->latest('id')
            ->take(15)
            ->get();

        $pending = $patron->reservations()
            ->pending()
            ->with('book')
            ->orderBy('reserved_at')
            ->get();

        $pastReservations = $patron->reservations()
            ->where('status', '!=', Reservation::PENDING)
            ->with('book')
            ->latest('reserved_at')
            ->take(5)
            ->get();

        $positions = Reservation::queuePositions($pending);

        $stats = [
            'active_loans'   => $activeLoans->count(),
            'overdue'        => $activeLoans->filter(fn ($l) => $l->isOverdue())->count(),
            'total_loans'    => $patron->loans()->count(),
            'visits_total'   => $patron->attendances()->count(),
            'visits_month'   => $patron->attendances()->where('visited_at', '>=', now()->startOfMonth())->count(),
            'pending'        => $pending->count(),
        ];

        return view('patrons.show', compact(
            'patron', 'activeLoans', 'history', 'visits', 'pending', 'pastReservations', 'positions', 'stats'
        ));
    }
}
