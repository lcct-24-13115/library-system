{{-- Web Search modal (staff layout only). Opened by the sidebar button [data-websearch-open].
     Self-contained: its own CSS and plain JS (no Tailwind/Alpine needed).
     Talks to GET /web-search/libraries and POST /web-search/go; the final address is
     built on the server from config/web_search.php. Text is inserted with textContent. --}}
<style>
    .ws-trigger { width: 100%; background: transparent; border: 0; cursor: pointer; text-align: left; font-family: inherit; }
    #salrc-websearch, #salrc-websearch * { box-sizing: border-box; font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }
    #salrc-websearch { position: fixed; inset: 0; z-index: 99998; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(10, 26, 64, .55); }
    #salrc-websearch.ws-open { display: flex; }
    #salrc-websearch .ws-panel { width: min(28rem, 100%); background: #fff; color: #0f1f3d; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.45); overflow: hidden; }
    #salrc-websearch .ws-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #1d4ed8; color: #fff; padding: 14px 18px; }
    #salrc-websearch .ws-head strong { display: flex; align-items: center; gap: 8px; font-size: 15px; }
    #salrc-websearch .ws-x { background: transparent; border: 0; color: #dbeafe; cursor: pointer; font-size: 26px; line-height: 1; padding: 0 4px; }
    #salrc-websearch .ws-x:hover { color: #fff; }
    #salrc-websearch .ws-body { display: flex; flex-direction: column; gap: 14px; padding: 18px; }
    #salrc-websearch label { display: block; font-size: 12px; font-weight: 700; color: #62708f; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .06em; }
    #salrc-websearch .ws-field { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #0f1f3d; background: #fff; outline: none; }
    #salrc-websearch .ws-field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .18); }
    #salrc-websearch .ws-go { display: flex; align-items: center; justify-content: center; gap: 8px; border: 0; cursor: pointer; background: #1d4ed8; color: #fff; font-weight: 700; font-size: 13px; letter-spacing: .06em; text-transform: uppercase; padding: 12px 16px; border-radius: 10px; }
    #salrc-websearch .ws-go:hover { background: #1e40af; }
    #salrc-websearch .ws-go[disabled] { opacity: .65; cursor: wait; }
    #salrc-websearch .ws-msg { display: none; font-size: 13px; padding: 10px 12px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    #salrc-websearch .ws-msg.ws-show { display: block; }
    #salrc-websearch .ws-msg a { color: #1d4ed8; font-weight: 700; text-decoration: underline; word-break: break-all; }
    #salrc-websearch .ws-hint { font-size: 12px; color: #62708f; }
    @media print { #salrc-websearch { display: none !important; } }
</style>

<div id="salrc-websearch" role="dialog" aria-modal="true" aria-labelledby="ws-title" aria-hidden="true"
     data-libraries-url="{{ route('websearch.libraries', [], false) }}"
     data-go-url="{{ route('websearch.go', [], false) }}"
     data-csrf="{{ csrf_token() }}">
    <div class="ws-panel">
        <div class="ws-head">
            <strong id="ws-title"><x-icon name="globe" :size="18" /> Web Search</strong>
            <button type="button" class="ws-x" data-websearch-close aria-label="Close">&times;</button>
        </div>

        <form class="ws-body" data-websearch-form novalidate>
            <div>
                <label for="ws-query">Search expression</label>
                <input id="ws-query" class="ws-field" type="search" maxlength="200" autocomplete="off"
                       placeholder="Title, author or subject" data-ws-query>
            </div>

            <div>
                <label for="ws-library">Library</label>
                <select id="ws-library" class="ws-field" data-ws-library>
                    <option value="">Loading libraries...</option>
                </select>
            </div>

            <div class="ws-msg" role="alert" data-ws-msg></div>

            <button type="submit" class="ws-go" data-ws-go>
                <x-icon name="globe" :size="18" /> Web Navigation
            </button>
            <div class="ws-hint">Opens the library's website in a new tab.</div>
        </form>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('salrc-websearch');
    if (!root) { return; }

    var form = root.querySelector('[data-websearch-form]');
    var query = root.querySelector('[data-ws-query]');
    var select = root.querySelector('[data-ws-library]');
    var msg = root.querySelector('[data-ws-msg]');
    var goBtn = root.querySelector('[data-ws-go]');
    var loaded = false;
    var STORE_KEY = 'salrc.websearch.library';

    // Per-browser convenience only. Can fail (private mode, blocked storage), so always guarded.
    function remembered() {
        try { return window.localStorage.getItem(STORE_KEY); } catch (err) { return null; }
    }
    function remember(value) {
        try { window.localStorage.setItem(STORE_KEY, value); } catch (err) { /* ignore */ }
    }
    function hasOption(value) {
        return Array.prototype.some.call(select.options, function (o) { return o.value === value; });
    }

    function showMessage(text, url) {
        msg.textContent = text;
        if (url) {
            msg.appendChild(document.createTextNode(' '));
            var a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = 'Open it here';
            msg.appendChild(a);
        }
        msg.classList.add('ws-show');
    }

    function clearMessage() {
        msg.textContent = '';
        msg.classList.remove('ws-show');
    }

    function loadLibraries() {
        select.disabled = true;
        fetch(root.dataset.librariesUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error('bad'); } return r.json(); })
            .then(function (data) {
                select.textContent = '';
                data.libraries.forEach(function (lib) {
                    var o = document.createElement('option');
                    o.value = lib.key;
                    o.textContent = lib.name;
                    select.appendChild(o);
                });
                if (data.default) { select.value = data.default; }
                var saved = remembered();
                if (saved && hasOption(saved)) { select.value = saved; }
                select.disabled = false;
                loaded = true;
            })
            .catch(function () {
                select.textContent = '';
                var o = document.createElement('option');
                o.value = '';
                o.textContent = 'Could not load libraries';
                select.appendChild(o);
                showMessage('Could not load the library list. Close this window and try again.');
            });
    }

    function open(prefill) {
        clearMessage();
        if (typeof prefill === 'string' && prefill !== '') { query.value = prefill.slice(0, 200); }
        root.classList.add('ws-open');
        root.setAttribute('aria-hidden', 'false');
        if (!loaded) { loadLibraries(); }
        setTimeout(function () { query.focus(); query.select(); }, 30);
    }

    function close() {
        root.classList.remove('ws-open');
        root.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-websearch-open]');
        if (trigger) { e.preventDefault(); open(trigger.getAttribute('data-websearch-query')); }
    });
    select.addEventListener('change', function () { if (select.value) { remember(select.value); } });
    root.querySelector('[data-websearch-close]').addEventListener('click', close);
    root.addEventListener('mousedown', function (e) { if (e.target === root) { close(); } });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root.classList.contains('ws-open')) { close(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearMessage();

        if (!select.value) { showMessage('Please choose a library.'); return; }

        goBtn.disabled = true;
        fetch(root.dataset.goUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': root.dataset.csrf
            },
            body: JSON.stringify({ library: select.value, query: query.value })
        })
            .then(function (r) {
                return r.json().then(function (data) { return { ok: r.ok, data: data }; });
            })
            .then(function (res) {
                if (!res.ok) {
                    var errors = res.data && res.data.errors;
                    var first = errors ? errors[Object.keys(errors)[0]][0] : (res.data && res.data.message);
                    throw new Error(first || 'Request failed');
                }
                remember(select.value);
                // Note: window.open() with the 'noopener' feature ALWAYS returns null, even when the tab
                // opens, so it cannot be used to detect a pop-up blocker. Open normally, then cut the link.
                var win = window.open(res.data.url, '_blank');
                if (win) {
                    try { win.opener = null; } catch (err) { /* ignore */ }
                    // The modal stays open on purpose, so the user can search again or try another library.
                } else {
                    // Pop-up blocker: let the user open it with one click.
                    showMessage('Your browser blocked the new tab.', res.data.url);
                }
            })
            .catch(function (err) {
                showMessage(err && err.message ? err.message : 'Something went wrong. Please try again.');
            })
            .then(function () { goBtn.disabled = false; });
    });
})();
</script>
