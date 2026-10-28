<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Patron;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only "Utilities" hub (legacy screen): one page with three tabs.
 * Phase 2: Administration (add / edit a user) and Users List.
 * Phase 3: Settings (excluded days, report heading, picture path, holidays / suspensions).
 * Phase 4: Group and Policy/Services panels inside the Settings tab (?panel=group|policy).
 * The forms post to the existing UserController routes (same validation, same rules).
 * Accounts are deactivated, never deleted.
 */
class UtilitiesController extends Controller
{
    /** tab key => label. The key is also the partial name: utilities/_{key}.blade.php */
    public const TABS = [
        'administration' => 'Administration',
        'users'          => 'Users List',
        'settings'       => 'Settings',
    ];

    /** role value => label shown as "Access Right". */
    public const ROLE_LABELS = [
        'admin'     => 'Administrator',
        'librarian' => 'Librarian',
        'staff'     => 'Staff',
    ];

    public function index(Request $request, LibrarySettings $settings)
    {
        $tab = (string) $request->query('tab', 'administration');

        // Unknown tab names fall back to the default instead of erroring.
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'administration';
        }

        $data = [
            'tabs'       => self::TABS,
            'tab'        => $tab,
            'roleLabels' => self::ROLE_LABELS,
            'editUser'   => null,
        ];

        if ($tab === 'administration') {
            $data['editUser'] = $this->editUser($request);
        }

        if ($tab === 'users') {
            $data += $this->usersData($request);
        }

        if ($tab === 'settings') {
            $data += $this->settingsData($request, $settings);
        }

        return view('utilities.index', $data);
    }

    private function settingsData(Request $request, LibrarySettings $settings): array
    {
        // ?edit_holiday={id} loads one holiday / suspension date into the form.
        $editHoliday = null;
        $id = $request->query('edit_holiday');
        if (is_scalar($id) && ctype_digit((string) $id)) {
            $editHoliday = Holiday::find((int) $id);
        }

        $panel = (string) $request->query('panel', '');
        $panel = in_array($panel, ['group', 'policy'], true) ? $panel : null;

        $data = [
            'days'        => LibrarySettings::DAYS,
            'excluded'    => $settings->excludedDays(),
            'heading'     => $settings->reportHeading(),
            'picturePath' => $settings->picturePath(),
            'holidays'    => Holiday::orderBy('date')->get(),
            'editHoliday' => $editHoliday,
            'panel'       => $panel,
        ];

        if ($panel === 'policy') {
            $data['loanDays'] = $settings->loanDays();
            $data['limits'] = [];
            foreach (Patron::TYPES as $type) {
                $data['limits'][$type] = $settings->maxLoans($type);
            }
        }

        if ($panel === 'group') {
            $data['groups'] = $this->groups($settings);
        }

        return $data;
    }

    /** One row per patron group (read only): patron counts plus the loan policy that applies. */
    private function groups(LibrarySettings $settings): array
    {
        $counts = Patron::query()
            ->selectRaw("patron_type, count(*) as total, sum(case when status = 'active' then 1 else 0 end) as active")
            ->groupBy('patron_type')
            ->get()
            ->keyBy('patron_type');

        $rows = [];
        foreach (Patron::TYPES as $type) {
            $rows[] = [
                'type'      => $type,
                'total'     => (int) ($counts[$type]->total ?? 0),
                'active'    => (int) ($counts[$type]->active ?? 0),
                'loan_days' => $settings->loanDays(),
                'max_loans' => $settings->maxLoans($type),
            ];
        }

        return $rows;
    }

    /** The staff account chosen with ?edit={id}. Portal (patron) accounts and unknown ids are ignored. */
    private function editUser(Request $request): ?User
    {
        $id = $request->query('edit');

        if (! is_scalar($id) || ! ctype_digit((string) $id)) {
            return null;
        }

        return User::query()
            ->where('role', '!=', User::PATRON_ROLE)
            ->find((int) $id);
    }

    private function usersData(Request $request): array
    {
        $userType = (string) $request->query('user_type', '');

        $users = User::query()
            ->where('role', '!=', User::PATRON_ROLE)
            ->when(in_array($userType, User::USER_TYPES, true), fn ($q) => $q->where('user_type', $userType))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // "Current user" = has an active session in the last 5 minutes (needs SESSION_DRIVER=database).
        $onlineIds = [];
        if (config('session.driver') === 'database') {
            $onlineIds = DB::table(config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();
        }

        return compact('users', 'userType', 'onlineIds');
    }
}
