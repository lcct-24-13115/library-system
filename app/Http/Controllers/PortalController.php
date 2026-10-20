<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Reservation;
use Illuminate\Http\Request;

/** Student / Faculty portal. Read only; each person sees only their own records. No fines or amounts. */
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
}
