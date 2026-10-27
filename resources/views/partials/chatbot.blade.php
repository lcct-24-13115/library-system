{{-- SALRC Library Assistant: floating chat widget. Self-contained (its own CSS, no Tailwind needed).
     Talks to POST /chatbot/message. All text is inserted with textContent (no HTML injection). --}}
@php
    $chatUser = auth()->user();
    $chatIsPatron = $chatUser && method_exists($chatUser, 'isPatron') && $chatUser->isPatron();
    $chatChips = $chatIsPatron
        ? ['Find a book', 'My due dates', 'My reservations', 'How to borrow']
        : ['Find a book', 'How to borrow', 'Library hours', 'Fines'];
@endphp

<style>
    #salrc-chatbot, #salrc-chatbot * { box-sizing: border-box; font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }
    #salrc-chatbot { position: fixed; right: 20px; bottom: 20px; z-index: 99999; font-size: 14px; line-height: 1.4; }
    #salrc-chatbot .cb-btn { display: flex; align-items: center; gap: 8px; border: 0; cursor: pointer; background: #1d4ed8; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 18px; border-radius: 999px; box-shadow: 0 8px 24px rgba(0,0,0,.35); }
    #salrc-chatbot .cb-btn:hover { background: #1e40af; }
    #salrc-chatbot .cb-btn svg { width: 20px; height: 20px; }
    #salrc-chatbot .cb-panel { display: none; flex-direction: column; width: min(24rem, calc(100vw - 2rem)); height: min(34rem, 80vh); background: #fff; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.45); overflow: hidden; }
    #salrc-chatbot.cb-open .cb-panel { display: flex; }
    #salrc-chatbot.cb-open .cb-btn { display: none; }
    #salrc-chatbot .cb-head { display: flex; align-items: center; justify-content: space-between; background: #1d4ed8; color: #fff; padding: 12px 16px; }
    #salrc-chatbot .cb-head strong { display: block; font-size: 14px; }
    #salrc-chatbot .cb-head small { font-size: 12px; color: #bfdbfe; }
    #salrc-chatbot .cb-head button { background: transparent; border: 0; color: #dbeafe; cursor: pointer; font-size: 12px; padding: 4px 8px; }
    #salrc-chatbot .cb-head button:hover { color: #fff; }
    #salrc-chatbot .cb-head .cb-x { font-size: 24px; line-height: 1; }
    #salrc-chatbot .cb-log { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px; background: #fff; }
    #salrc-chatbot .cb-row { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
    #salrc-chatbot .cb-row.cb-me { align-items: flex-end; }
    #salrc-chatbot .cb-bubble { max-width: 85%; padding: 8px 12px; border-radius: 16px; white-space: pre-line; word-break: break-word; background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
    #salrc-chatbot .cb-me .cb-bubble { background: #1d4ed8; color: #fff; border-bottom-left-radius: 16px; border-bottom-right-radius: 4px; }
    #salrc-chatbot .cb-card { width: 92%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    #salrc-chatbot .cb-card b { display: block; color: #0f172a; }
    #salrc-chatbot .cb-card span.cb-meta { display: block; font-size: 12px; color: #64748b; margin-top: 2px; }
    #salrc-chatbot .cb-badge { display: inline-block; margin-top: 8px; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    #salrc-chatbot .cb-ok { background: #e0f2fe; color: #0369a1; }
    #salrc-chatbot .cb-no { background: #fef3c7; color: #b45309; }
    #salrc-chatbot .cb-link { font-weight: 600; color: #1d4ed8; text-decoration: underline; }
    #salrc-chatbot .cb-typing { font-size: 12px; color: #94a3b8; padding: 0 4px; }
    #salrc-chatbot .cb-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 12px 8px; background: #fff; }
    #salrc-chatbot .cb-chip { cursor: pointer; font-size: 12px; font-weight: 600; color: #1d4ed8; background: #eff6ff; border: 1px solid #dbeafe; border-radius: 999px; padding: 4px 12px; }
    #salrc-chatbot .cb-chip:hover { background: #dbeafe; }
    #salrc-chatbot .cb-form { display: flex; gap: 8px; align-items: center; padding: 12px; border-top: 1px solid #e2e8f0; background: #fff; }
    #salrc-chatbot .cb-input { flex: 1; min-width: 0; border: 1px solid #cbd5e1; border-radius: 999px; padding: 8px 14px; font-size: 14px; color: #1e293b; background: #fff; outline: none; }
    #salrc-chatbot .cb-input:focus { border-color: #2563eb; }
    #salrc-chatbot .cb-send { border: 0; cursor: pointer; background: #1d4ed8; color: #fff; font-weight: 600; font-size: 14px; border-radius: 999px; padding: 8px 16px; }
    #salrc-chatbot .cb-send:hover { background: #1e40af; }
    @media print { #salrc-chatbot { display: none !important; } }
</style>

<div id="salrc-chatbot"
     data-url="{{ route('chatbot.message', [], false) }}"
     data-csrf="{{ csrf_token() }}"
     data-uid="{{ $chatUser ? 'u'.$chatUser->id : 'guest' }}"
     data-chips="{{ json_encode($chatChips) }}"
     data-welcome="Hi! I'm the SALRC Library Assistant. Ask me to find a book{{ $chatIsPatron ? ', check your due dates' : '' }} or how borrowing works. Pwede rin sa Tagalog!">

    <button type="button" class="cb-btn" data-chat-toggle aria-label="Open library assistant" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6l2.2-3.3A8 8 0 1 1 12 20H4z"/>
        </svg>
        <span>Ask Library</span>
    </button>

    <section class="cb-panel" data-chat-panel role="dialog" aria-label="SALRC Library Assistant">
        <header class="cb-head">
            <div>
                <strong>SALRC Library Assistant</strong>
                <small>English / Tagalog</small>
            </div>
            <div>
                <button type="button" data-chat-clear>Clear</button>
                <button type="button" class="cb-x" data-chat-close aria-label="Close">&times;</button>
            </div>
        </header>

        <div class="cb-log" data-chat-log aria-live="polite"></div>
        <div class="cb-chips" data-chat-chips></div>

        <form class="cb-form" data-chat-form autocomplete="off">
            <input type="text" class="cb-input" data-chat-input maxlength="300" placeholder="Type a title, author or question...">
            <button type="submit" class="cb-send">Send</button>
        </form>
    </section>
</div>

<script>
(function () {
    var root = document.getElementById('salrc-chatbot');
    if (!root) return;

    var url   = root.dataset.url;
    var uid   = root.dataset.uid;
    var csrf  = root.dataset.csrf || '';
    var toggle = root.querySelector('[data-chat-toggle]');
    var log    = root.querySelector('[data-chat-log]');
    var chipsBox = root.querySelector('[data-chat-chips]');
    var form   = root.querySelector('[data-chat-form]');
    var input  = root.querySelector('[data-chat-input]');
    var KEY = 'salrc-chat-v1';
    var history = [];
    var chips = [];
    var busy = false;

    try { chips = JSON.parse(root.dataset.chips) || []; } catch (e) { chips = []; }

    /* Kept per signed-in user, so a shared computer never shows someone else's chat. */
    function save() {
        try { sessionStorage.setItem(KEY, JSON.stringify({ uid: uid, history: history, chips: chips })); } catch (e) {}
    }
    function load() {
        try {
            var raw = sessionStorage.getItem(KEY);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (data && data.uid === uid && Array.isArray(data.history)) {
                history = data.history;
                if (Array.isArray(data.chips) && data.chips.length) chips = data.chips;
            } else {
                sessionStorage.removeItem(KEY);
            }
        } catch (e) {}
    }

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function scrollDown() { log.scrollTop = log.scrollHeight; }

    function renderMessage(m) {
        var row = el('div', 'cb-row' + (m.from === 'me' ? ' cb-me' : ''));
        row.appendChild(el('div', 'cb-bubble', m.text));

        (m.books || []).forEach(function (b) {
            var card = el('div', 'cb-card');
            card.appendChild(el('b', '', b.title));
            if (b.author) card.appendChild(el('span', 'cb-meta', b.author));
            if (b.where) card.appendChild(el('span', 'cb-meta', b.where));
            row.appendChild(card);
        });

        (m.links || []).forEach(function (l) {
            if (typeof l.url !== 'string' || l.url.charAt(0) !== '/') return; // same-site links only
            var a = el('a', 'cb-link', l.label);
            a.href = l.url;
            row.appendChild(a);
        });

        log.appendChild(row);
        scrollDown();
    }

    function renderChips() {
        chipsBox.textContent = '';
        chips.forEach(function (label) {
            var b = el('button', 'cb-chip', label);
            b.type = 'button';
            b.addEventListener('click', function () { send(label); });
            chipsBox.appendChild(b);
        });
    }

    function add(m) {
        history.push(m);
        if (history.length > 40) history = history.slice(-40);
        renderMessage(m);
        save();
    }

    function typing(on) {
        var existing = log.querySelector('[data-typing]');
        if (existing) existing.remove();
        if (on) {
            var t = el('div', 'cb-typing', 'Typing...');
            t.setAttribute('data-typing', '1');
            log.appendChild(t);
            scrollDown();
        }
    }

    function botError(text) { add({ from: 'bot', text: text, books: [], links: [] }); }

    function send(text) {
        text = (text || '').trim();
        if (!text || busy) return;

        busy = true;
        add({ from: 'me', text: text });
        input.value = '';
        typing(true);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message: text })
        }).then(function (res) {
            if (res.status === 419) throw new Error('expired');
            if (res.status === 429) throw new Error('throttled');
            if (!res.ok) throw new Error('failed');
            return res.json();
        }).then(function (data) {
            typing(false);
            if (Array.isArray(data.suggestions) && data.suggestions.length) { chips = data.suggestions; renderChips(); }
            add({ from: 'bot', text: data.reply, books: data.books || [], links: data.links || [] });
        }).catch(function (err) {
            typing(false);
            if (err.message === 'expired') botError('Your page has expired. Please refresh the page and try again.');
            else if (err.message === 'throttled') botError('You are sending messages too fast. Please wait a moment.');
            else botError('Sorry, something went wrong. Please try again.');
        }).then(function () {
            busy = false;
            input.focus();
        });
    }

    function openPanel() {
        root.classList.add('cb-open');
        toggle.setAttribute('aria-expanded', 'true');
        if (history.length === 0) {
            add({ from: 'bot', text: root.dataset.welcome, books: [], links: [] });
        }
        renderChips();
        input.focus();
        scrollDown();
    }

    function closePanel() {
        root.classList.remove('cb-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    toggle.addEventListener('click', openPanel);
    root.querySelector('[data-chat-close]').addEventListener('click', closePanel);
    root.querySelector('[data-chat-clear]').addEventListener('click', function () {
        history = [];
        log.textContent = '';
        try { sessionStorage.removeItem(KEY); } catch (e) {}
        add({ from: 'bot', text: root.dataset.welcome, books: [], links: [] });
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('cb-open')) closePanel(); });

    load();
    history.forEach(renderMessage);
})();
</script>
