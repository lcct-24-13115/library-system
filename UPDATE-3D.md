# UPDATE-3D — 3D redesign (additive, no database changes)

No migrations, no composer/npm, no business-rule changes. Fines/penalties stay out; overdue never blocks.

## New files
- public/css/salrc-3d.css   — global 3D theme (re-skins existing Tailwind classes: cards, tables, buttons, inputs, sidebar, topbar)
- public/css/login-3d.css   — login card depth + tactile button
- public/js/salrc-3d.js     — tilt, Three.js background (loaded from cdnjs, r128), shelf/table switch, 3D book modal
- resources/views/books/_shelf.blade.php, _book3d.blade.php, _book-modal.blade.php — 3D bookshelf

## Changed files
- layouts/_head.blade.php (Tailwind shadows/keyframes/3D utilities + the two asset links)
- layouts/portal.blade.php, layouts/guest.blade.php (one class / one attribute)
- auth/login.blade.php, dashboard.blade.php, books/index.blade.php, catalog/index.blade.php
- loans/return.blade.php, loans/create.blade.php (scan-panel class + laser)

## Roll back
Remove the two lines at the bottom of layouts/_head.blade.php (salrc-3d.css / salrc-3d.js) and the 3D lines in auth/login.blade.php,
or simply `git checkout -- .` before committing.

## Tailwind utilities added (CDN config in _head)
shadow-tactile, shadow-tactile-blue, shadow-depth, shadow-depth-lg, animate-floaty, animate-rise3d,
preserve-3d, backface-hidden, perspective-600/1000/1500, rotate-x-{3,6,12,45}, rotate-y-{3,6,12,45}, translate-z-{10,20,40}
(hover:/focus:/active: variants work, e.g. `hover:rotate-x-3 active:translate-y-1`).
