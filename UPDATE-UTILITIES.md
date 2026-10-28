# Utilities module (legacy "Utilities" screen) - summary

Admin only. One page with three tabs, `/utilities`:

| Tab | URL | What it does |
|---|---|---|
| Administration | `/utilities?tab=administration` (`&edit={userId}`) | Add / edit a staff account: User Name, Email, Password, Access Right, User Type, Library |
| Users List | `/utilities?tab=users` | Accounts with Current User, Access right, Library, Status; Edit, Reset password, Deactivate / Reactivate |
| Settings | `/utilities?tab=settings` | Excluded days, Report heading, Picture path, Holiday / Suspension dates (Add, Edit, Delete, Empty) |
| Settings > Group | `/utilities?tab=settings&panel=group` | Read-only list of the 4 patron groups with counts and policy |
| Settings > Policy/Services | `/utilities?tab=settings&panel=policy` | Default loan days and books allowed per group |

## How it fits the existing code
- The tabs reuse the existing controllers and validation (`UserController`, `SettingsController`, `LibrarySettings`). A hidden `from=utilities` field makes them redirect back into Utilities; without it they behave exactly as before.
- The old pages (`/users`, `/users/create`, `/settings`, `/settings/policy`) still work, so bookmarks and the existing tests are unaffected. They are no longer in the sidebar; "Utilities" is highlighted on them.
- Accounts are never deleted (the old "Delete" = Deactivate). The role of your own account cannot be changed or deactivated.
- No fines, amounts or payments anywhere. Excluded days / holidays only change the "days overdue" shown.
- No database migration was needed (Picture path uses the `settings` table).

## Files added / changed over all phases
- New: `UtilitiesController`, `resources/views/utilities/*`, tests `UtilitiesHubTest`, `UtilitiesAdminUsersTest`, `UtilitiesSettingsTest`, `UtilitiesPolicyGroupTest`, `UtilitiesSidebarTest`, and the `UPDATE-UTILITIES-PHASE1..4.md` notes.
- Changed: `routes/web.php`, `UserController` (2 redirects), `SettingsController` (redirects, picture path, holiday update, policy redirect), `LibrarySettings` (`picturePath()`), `_sidebar`, `_topbar`, `icon`.

## Not done on purpose
- Adding / renaming patron groups (see `UPDATE-UTILITIES-PHASE4.md`).
- Login by username instead of email (the system signs in with email).
- Wiring "Picture path" to patron photos (photos are uploaded per patron and stored in `storage`).
