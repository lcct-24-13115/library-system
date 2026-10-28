<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Admin-only "Utilities" hub (legacy screen): one page with three tabs.
 * Phase 1 is only the shell (tab switching). The tab contents are filled in later phases.
 */
class UtilitiesController extends Controller
{
    /** tab key => label. The key is also the partial name: utilities/_{key}.blade.php */
    public const TABS = [
        'administration' => 'Administration',
        'users'          => 'Users List',
        'settings'       => 'Settings',
    ];

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'administration');

        // Unknown tab names fall back to the default instead of erroring.
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'administration';
        }

        return view('utilities.index', [
            'tabs' => self::TABS,
            'tab'  => $tab,
        ]);
    }
}
