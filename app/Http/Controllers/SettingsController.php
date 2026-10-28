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
            'picture_path'    => ['nullable', 'string', 'max:255', 'not_regex:/\.\./', 'not_regex:/[\x00-\x1F]/'],
        ]);

        $settings->set('excluded_days', json_encode(array_values(array_map('intval', $data['excluded_days'] ?? []))));
        $settings->set('report_heading', filled($data['report_heading'] ?? null) ? trim($data['report_heading']) : null);

        // Picture path is only saved when the field is sent (the old Settings page does not have it).
        if ($request->has('picture_path')) {
            $settings->set('picture_path', filled($data['picture_path'] ?? null) ? trim($data['picture_path']) : null);
        }

        return $this->settingsRedirect()->with('success', 'Settings saved.');
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

        return $this->settingsRedirect()->with('success', 'Holiday / suspension added.');
    }

    public function holidayDestroy(Holiday $holiday, LibrarySettings $settings)
    {
        $holiday->delete();
        $settings->refresh();

        return $this->settingsRedirect()->with('success', 'Holiday / suspension removed.');
    }

    public function holidayEmpty(LibrarySettings $settings)
    {
        Holiday::query()->delete();
        $settings->refresh();

        return $this->settingsRedirect()->with('success', 'Holiday / suspension list emptied.');
    }

    /** Edit one holiday / suspension date (and its reason). */
    public function holidayUpdate(Request $request, Holiday $holiday, LibrarySettings $settings)
    {
        $data = $request->validate([
            'date'   => ['required', 'date_format:Y-m-d', Rule::unique('holidays', 'date')->ignore($holiday->id)],
            'reason' => ['nullable', 'string', 'max:150'],
        ], [
            'date.unique' => 'That date is already in the holiday / suspension list.',
        ]);

        $holiday->update($data);
        $settings->refresh();

        return $this->settingsRedirect()->with('success', 'Holiday / suspension updated.');
    }

    /** After saving: back to the Utilities Settings tab when the form came from there, else the old page. */
    private function settingsRedirect()
    {
        return request()->input('from') === 'utilities'
            ? redirect()->route('utilities.index', ['tab' => 'settings'])
            : redirect()->route('settings.index');
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

        return (request()->input('from') === 'utilities'
            ? redirect()->route('utilities.index', ['tab' => 'settings', 'panel' => 'policy'])
            : redirect()->route('settings.policy'))->with('success', 'Policy saved.');
    }
}
