# Utilities - Phase 1: hub page and sidebar

Additive only. No migration, no existing route or data changed.

## Added
- `GET /utilities` (`utilities.index`), admin only (`role:admin`). Tab chosen by `?tab=administration|users|settings`; anything else falls back to `administration`.
- `app/Http/Controllers/UtilitiesController.php`
- Views: `resources/views/utilities/index.blade.php`, `_tabs.blade.php`, and placeholder partials `_administration`, `_users`, `_settings` (filled in later phases).
- Sidebar: "Utilities" link at the top of the Admin group (admins only). The existing "Users" and "Settings" links are kept.
- Icon `utilities` in `components/icon.blade.php`.
- Tests: `tests/Feature/UtilitiesHubTest.php`.

## Not changed
- Users, Settings, Policy, holidays logic and routes.
