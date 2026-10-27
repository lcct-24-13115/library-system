<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Patron;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Attendance (daily visitor log).
 *
 * - Scan or type a patron ID -> one visit row is logged.
 * - Hourly statistics for any chosen day + visitor history log.
 * - Log only: no fines, and nothing here blocks borrowing. Everything is
 *   shown on screen (print via the browser); no files are generated.
 */
class AttendanceController extends Controller
{
    /**
     * "Record your visit" screen: type / scan a student ID, confirm the photo,
     * tap Record entry. Footer cards show live counters.
     */
    public function index()
    {
        return view('attendance.index', ['summary' => $this->summary()]);
    }

    /** Live ID lookup (JSON) for the kiosk: returns the photo + name so identity can be confirmed. */
    public function lookup(Request $request): JsonResponse
    {
        $idNumber = trim((string) $request->query('id_number'));
        $patron   = $idNumber === '' ? null : Patron::where('id_number', $idNumber)->first();

        if (! $patron) {
            return response()->json(['found' => false]);
        }

        return response()->json(['found' => true] + $this->patronPayload($patron));
    }

    /** Footer counters as JSON (kept fresh while the kiosk stays open). */
    public function summaryJson(): JsonResponse
    {
        return response()->json($this->summary());
    }

    /** Staff analytics: hourly chart, 14-day totals and the day log for a chosen day (default: today). */
    public function stats(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);

        $date    = $request->filled('date') ? Carbon::parse($request->input('date'))->startOfDay() : today();
        $isToday = $date->isSameDay(today());

        $visits = Attendance::with('patron')
            ->onDate($date->toDateString())
            ->orderByDesc('visited_at')
            ->orderByDesc('id')
            ->get();

        // Hourly buckets (hour of day in the app timezone).
        $byHour = $visits->groupBy(fn ($v) => (int) $v->visited_at->format('G'))->map->count();

        $firstHour = min(7, (int) ($byHour->keys()->min() ?? 7));
        $lastHour  = max(19, (int) ($byHour->keys()->max() ?? 19));

        $hourly = collect(range($firstHour, $lastHour))->map(fn ($h) => [
            'hour'  => $h,
            'label' => Carbon::createFromTime($h)->format('gA'),
            'count' => (int) ($byHour[$h] ?? 0),
        ]);

        $hourlyMax = max(1, $hourly->max('count'));
        $peak      = $hourly->sortByDesc('count')->first();
        $peakHour  = ($peak && $peak['count'] > 0) ? $peak : null;

        $stats = [
            'total'   => $visits->count(),
            'unique'  => $visits->pluck('patron_id')->unique()->count(),
            'by_type' => $visits->groupBy(fn ($v) => $v->patron->patron_type ?? 'Unknown')->map->count()->sortDesc(),
        ];

        // Daily totals for the 14 days ending on the chosen date.
        $from  = $date->copy()->subDays(13);
        $daily = Attendance::selectRaw('DATE(visited_at) as d, COUNT(*) as c')
            ->whereDate('visited_at', '>=', $from->toDateString())
            ->whereDate('visited_at', '<=', $date->toDateString())
            ->groupBy('d')
            ->pluck('c', 'd');

        $dailyChart = collect(range(0, 13))->map(function ($i) use ($from, $daily) {
            $day = $from->copy()->addDays($i);

            return [
                'label' => $day->format('M j'),
                'dow'   => $day->format('D'),
                'date'  => $day->toDateString(),
                'count' => (int) ($daily[$day->toDateString()] ?? 0),
            ];
        });
        $dailyMax = max(1, $dailyChart->max('count'));

        return view('attendance.stats', [
            'date'       => $date,
            'isToday'    => $isToday,
            'visits'     => $visits->take(25),
            'stats'      => $stats,
            'hourly'     => $hourly,
            'hourlyMax'  => $hourlyMax,
            'peakHour'   => $peakHour,
            'dailyChart' => $dailyChart,
            'dailyMax'   => $dailyMax,
        ]);
    }

    /** Log a visit. Returns JSON for the scan screen, or redirects for plain form posts. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patron_id_number' => ['required', 'string', 'max:255'],
            'remarks'          => ['nullable', 'string', 'max:255'],
        ]);

        $patron = Patron::where('id_number', trim($data['patron_id_number']))->first();

        if (! $patron) {
            throw ValidationException::withMessages([
                'patron_id_number' => 'No student found with that ID number. Please check your ID or ask the librarian.',
            ]);
        }

        // Ignore accidental double reads of the same card.
        $window = (int) config('library.attendance_duplicate_minutes', 5);
        $last   = $window > 0
            ? $patron->attendances()->where('visited_at', '>=', now()->subMinutes($window))->latest('visited_at')->first()
            : null;

        if ($last) {
            $payload = [
                'status'   => 'duplicate',
                'headline' => "{$patron->name} was already logged at {$last->visited_at->format('h:i A')}.",
                'detail'   => 'Duplicate scan ignored.',
                'patron'   => $this->patronPayload($patron),
                'summary'  => $this->summary(),
            ];

            return $request->expectsJson()
                ? response()->json($payload)
                : back()->with('error', $payload['headline'] . ' ' . $payload['detail']);
        }

        $visit = Attendance::create([
            'patron_id'   => $patron->id,
            'recorded_by' => $request->user()?->id,
            'visited_at'  => now(),
            'remarks'     => $data['remarks'] ?? null,
        ]);

        $todayCount = Attendance::onDate(today()->toDateString())->count();

        $payload = [
            'status'   => 'logged',
            'headline' => "Welcome, {$patron->name}!",
            'detail'   => "Logged at {$visit->visited_at->format('h:i A')} - visitor #{$todayCount} today.",
            // Informational only; inactive patrons are still logged, the librarian decides.
            'warning'  => $patron->isActive() ? null : 'This patron is marked inactive.',
            'patron'   => $this->patronPayload($patron),
            'summary'  => $this->summary(),
        ];

        return $request->expectsJson()
            ? response()->json($payload, 201)
            : back()->with('success', $payload['headline'] . ' ' . $payload['detail']);
    }

    /** Visitor history log with filters. */
    public function history(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $search = trim((string) $request->input('search'));
        $from   = $request->input('from');
        $to     = $request->input('to');

        $base = Attendance::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('patron', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('id_number', 'like', "%{$search}%");
                });
            })
            ->when($from, fn ($q) => $q->whereDate('visited_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('visited_at', '<=', $to));

        $total = (clone $base)->count();

        $visits = $base->with(['patron', 'recorder'])
            ->latest('visited_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('attendance.history', compact('visits', 'search', 'from', 'to', 'total'));
    }

    /** Remove a mistaken entry. */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return back()->with('success', 'Visit entry removed.');
    }

    private function patronPayload(Patron $patron): array
    {
        return [
            'id_number'   => $patron->id_number,
            'name'        => $patron->name,
            'patron_type' => $patron->patron_type,
            'status'      => $patron->status,
            'photo_url'   => $patron->photoUrl(),
            'initials'    => $patron->initials(),
        ];
    }

    /** Numbers for the three footer cards: visits today, unique visitors today, visits this week (Mon-Sun). */
    private function summary(): array
    {
        $today = Attendance::onDate(today()->toDateString());

        return [
            'today'  => (clone $today)->count(),
            'unique' => (clone $today)->distinct()->count('patron_id'),
            'week'   => Attendance::whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }
}
