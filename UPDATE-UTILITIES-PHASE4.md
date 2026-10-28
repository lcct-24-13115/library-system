# Utilities - Phase 4: Group and Policy/Services

Additive only. No migration.

## Settings tab panels
- The "Group / Policy setting" box now has two buttons: **Group** (`?tab=settings&panel=group`) and **Policy/Services** (`?tab=settings&panel=policy`). Any other `panel` value shows the normal Settings tab. Both have "Back to Settings".
- **Policy/Services**: default loan days and the borrow limit per group. Same form, fields and validation as `/settings/policy`; it posts to the existing `settings.policy.update` with a hidden `from=utilities`, and `SettingsController::policyUpdate` then returns to this panel. Without `from`, the old redirect to `/settings/policy` is unchanged.
- **Group** (read only): the four patron groups (Student, Faculty, Staff, Outside User) with total patrons, active patrons, loan days and books allowed.

## Why Group is read only
`Patron::TYPES` is used by the patron forms, the Excel/CSV import, the borrow limit (`config/library.php`, `LibrarySettings::maxLoans()`) and the "Outside User" rule. Adding or renaming groups would need those to become database driven and a rename would have to rewrite existing patrons. That is a separate, riskier change; do it only if the library really needs more groups.

## Files
- Changed: `UtilitiesController.php`, `SettingsController.php` (one redirect line), `utilities/_settings.blade.php` (now picks the panel).
- New: `utilities/_settings_main.blade.php`, `_settings_group.blade.php`, `_settings_policy.blade.php`, `tests/Feature/UtilitiesPolicyGroupTest.php`.

## Not changed
- Borrow limit and overdue rules, patrons, loans, users.
