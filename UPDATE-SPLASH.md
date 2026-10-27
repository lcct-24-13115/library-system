# Splash screen (book opens, then the sign-in page)

No migration, no package, no database change. Front end only.

## What it does
- Opening the site (`/`) shows a full-screen splash: a closed navy book with the seal, the cover swings open, "Welcome" and a small loading bar appear on the right page, then it fades into the sign-in page.
- About 5 seconds. Click, tap, any key or the Skip button goes to sign-in immediately.
- Shown ONCE per browser tab (sessionStorage). After logging out in the same tab, the sign-in page opens directly.
- The sign-in page then shows the book already open (it does not play its own opening a second time).
  Opening /login directly still plays the old closed-book opening.
- Signed-in users who open `/` go straight to their home (dashboard or My Library).
- If the visitor's system has "reduce motion" on, the book just appears open for about 1.5 seconds.

## Files
- resources/views/splash.blade.php (new, all CSS/JS inline)
- routes/web.php (`/` now returns the splash view, named `splash`)
- resources/views/auth/login.blade.php (head script: skip the closing animation when coming from the splash)
- tests/Feature/SplashScreenTest.php (new), tests/Feature/ExampleTest.php (`/` is now 200, was 302)

## Adjust
- Timing: the two numbers 4500 / 5000 (milliseconds) near the bottom of splash.blade.php.
- Book size: `--w` at the top of the CSS.
