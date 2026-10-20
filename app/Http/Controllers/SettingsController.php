<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Patron;
use App\Support\LibrarySettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin-only Settings page: weekdays / holidays that are not counted as "days overdue",
 * the report heading, and the loan policy (loan days + borrow limit per patron type).
 * Display / limits only. Nothing here computes fines or money.
 */
class SettingsController extends Controller
{
    public function index(LibrarySettings $settings)
    {
        return view('settings.index', [
            'days'     => LibrarySettings::DAYS,
            'excluded' => $settings->excludedDays(),
            'heading'  => $settings->reportHeading(),
            'holidays' => Holiday::orderBy('date')->get(),
        ]);
    }

    public function update(Request $request, LibrarySettings $settings)
    {
        $data = $request->validate([
            'excluded_days'   => ['nullable', 'array'],
            'excluded_days.*' => ['integer', 'between:0,6'],
            'report_heading'  => ['nullable', 'string', 'max:150'],
        ]);

        $settings->set('excluded_days', json_encode(array_values(array_map('intval', $data['excluded_days'] ?? []))));
        $settings->set('report_heading', filled($data['report_heading'] ?? null) ? trim($data['report_heading']) : null);

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }

    public function holidayStore(Request $request, LibrarySettings $settings)
    {
        $data = $request->validate([
            'date'   => ['required', 'date_format:Y-m-d', Rule::unique('holidays', 'date')],
            'reason' => ['nullable', 'string', 'max:150'],
        ], [
            'date.unique' => 'That date is already in the holiday / suspension list.',
        ]);

        Holiday::create($data);
        $settings->refresh();

        return redirect()->route('settings.index')->with('success', 'Holiday / suspension added.');
    }

    public function holidayDestroy(Holiday $holiday, LibrarySettings $settings)
    {
        $holiday->delete();
        $settings->refresh();

        return redirect()->route('settings.index')->with('success', 'Holiday / suspension removed.');
    }

    public function holidayEmpty(LibrarySettings $settings)
    {
        Holiday::query()->delete();
        $settings->refresh();

        return redirect()->route('settings.index')->with('success', 'Holiday / suspension list emptied.');
    }

    /** "Group / Policy": default loan days and borrow limit per patron group (type). */
    public function policy(LibrarySettings $settings)
    {
        $limits = [];
        foreach (Patron::TYPES as $type) {
            $limits[$type] = $settings->maxLoans($type);
        }

        return view('settings.policy', [
            'loanDays' => $settings->loanDays(),
            'limits'   => $limits,
        ]);
    }

    public function policyUpdate(Request $request, LibrarySettings $settings)
    {
        $data = $request->validate([
            'loan_days'    => ['required', 'integer', 'min:1', 'max:365'],
            'max_loans'    => ['required', 'array'],
            'max_loans.*'  => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $settings->set('loan_days', (string) $data['loan_days']);

        foreach (Patron::TYPES as $type) {
            if (isset($data['max_loans'][$type])) {
                $settings->set('max_loans.' . $type, (string) $data['max_loans'][$type]);
            }
        }

        return redirect()->route('settings.policy')->with('success', 'Policy saved.');
    }
}
