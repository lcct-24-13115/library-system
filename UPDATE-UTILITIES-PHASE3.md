# Utilities - Phase 3: Settings tab

Additive only. No migration (the Picture path uses the existing `settings` table, key `picture_path`).

## Settings tab (`/utilities?tab=settings`)
- Excluded days (all seven weekdays), Report heading, Picture path, Save Settings, Refresh.
- Holiday / Suspension dates: Add, Edit (new), Delete, Empty list, Close.
- "Group / Policy" button opens the existing Policy page (moved inside Utilities in Phase 4).

## Behaviour
- Forms post to the existing `settings.update`, `settings.holidays.*` routes with a hidden `from=utilities`; the controller then returns to the Utilities Settings tab. Without it, the old redirects to `/settings` are unchanged.
- New route `PUT /settings/holidays/{holiday}` (`settings.holidays.update`): date + reason; the date must stay unique (its own date is allowed).
- `picture_path`: optional, max 255, no `..`, no control characters. Only saved when the field is sent, so the old Settings page never wipes it. Blank clears it. `LibrarySettings::picturePath()` reads it. It is for reference only; ID photos are still uploaded on each patron page.
- Holiday edits call `LibrarySettings::refresh()`, so "days overdue" display updates immediately. Display only; no fines.

## Files
- Changed: `UtilitiesController.php`, `SettingsController.php`, `LibrarySettings.php`, `routes/web.php`, `utilities/_settings.blade.php`.
- New: `tests/Feature/UtilitiesSettingsTest.php`.

## Not changed
- Excluded-day defaults (nothing is checked until saved), policy rules, users, loans.
