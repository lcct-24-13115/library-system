{{--
    v3 live preview: shows Patron and Book details while the librarian types or scans.
    Expects inputs named "patron_id_number" and "accession_number" on the same page.
    $mode = 'issue' | 'reserve'
    Read-only; nothing here blocks the form. The server re-validates on submit.
--}}
@php $mode = $mode ?? 'issue'; @endphp

<div id="lookup-preview"
     class="grid grid-cols-1 md:grid-cols-2 gap-4"
     data-mode="{{ $mode }}"
     data-patron-url="{{ route('lookup.patron') }}"
     data-book-url="{{ route('lookup.book') }}"
     data-issue-url="{{ route('loans.create') }}"
     data-reserve-url="{{ route('reservations.create') }}"
     data-reservations-url="{{ route('reservations.index') }}">
    <div id="preview-patron" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-400"></div>
    <div id="preview-book" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-400"></div>
</div>

<script>
(function () {
    const root = document.getElementById('lookup-preview');
    if (!root) return;

    const patronInput = document.querySelector('input[name="patron_id_number"]');
    const bookInput   = document.querySelector('input[name="accession_number"]');
    if (!patronInput || !bookInput) return;

    const mode      = root.dataset.mode;
    const patronBox = document.getElementById('preview-patron');
    const bookBox   = document.getElementById('preview-book');

    const TONES = {
        idle: 'border-dashed border-slate-300 bg-slate-50 text-slate-400',
        ok:   'border-emerald-300 bg-emerald-50 text-slate-800',
        warn: 'border-amber-300 bg-amber-50 text-slate-800',
        bad:  'border-rose-300 bg-rose-50 text-slate-800',
        load: 'border-slate-200 bg-white text-slate-400',
    };

    // Safe DOM helpers: all server text goes in via textContent (never innerHTML).
    function el(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined && text !== null) n.textContent = text;
        return n;
    }
    function pill(text, cls) {
        return el('span', 'inline-block px-2 py-0.5 rounded-full text-xs font-semibold ' + cls, text);
    }
    function link(text, href, newTab) {
        const a = el('a', 'text-indigo-700 font-semibold hover:underline', text);
        a.href = href;
        if (newTab) { a.target = '_blank'; a.rel = 'noopener'; }
        return a;
    }
    function paint(box, tone, nodes) {
        box.className = 'rounded-lg border p-4 text-sm ' + TONES[tone];
        box.replaceChildren(...nodes);
    }
    function idle(box, kind) {
        paint(box, 'idle', [el('p', '', kind === 'patron' ? 'Patron details will appear here.' : 'Book details will appear here.')]);
    }

    function renderPatron(d) {
        if (!d.found) {
            return paint(patronBox, 'bad', [
                el('p', 'font-bold text-rose-700', 'No patron found'),
                el('p', 'text-xs text-slate-600 mt-1', 'Check the ID number, or register the patron first.'),
            ]);
        }

        const atLimit = mode === 'issue' && d.active_loans_count >= d.max_loans;
        const tone = !d.is_active ? 'bad' : ((d.overdue_count > 0 || atLimit) ? 'warn' : 'ok');
        const nodes = [];

        const head = el('div', 'flex items-start justify-between gap-2');
        const title = el('div');
        title.append(el('p', 'font-bold text-slate-900 text-base', d.name), el('p', 'text-xs text-slate-500', d.id_number));
        const badges = el('div', 'flex flex-wrap gap-1 justify-end');
        badges.append(
            pill(d.patron_type, 'bg-slate-200 text-slate-700'),
            d.is_active ? pill('Active', 'bg-emerald-100 text-emerald-700') : pill('Inactive', 'bg-rose-100 text-rose-700')
        );
        head.append(title, badges);
        nodes.push(head);

        const contact = [d.email, d.contact_no].filter(Boolean).join(' · ');
        if (contact) nodes.push(el('p', 'text-xs text-slate-600 mt-2', contact));

        nodes.push(el('p', 'text-xs text-slate-700 mt-2',
            'On loan: ' + d.active_loans_count + ' of ' + d.max_loans + ' allowed · Overdue: ' + d.overdue_count + ' · Pending reservations: ' + d.pending_reservations_count));

        if (!d.is_active) {
            nodes.push(el('p', 'text-xs font-semibold text-rose-700 mt-2', 'Marked inactive: cannot ' + (mode === 'issue' ? 'borrow' : 'reserve') + ' until set to active.'));
        }
        if (atLimit) {
            nodes.push(el('p', 'text-xs font-semibold text-amber-800 mt-2', 'At the borrow limit. A librarian/admin must tick "Override limit" (with a remark) to check out another book.'));
        }
        if (d.overdue_count > 0) {
            nodes.push(el('p', 'text-xs text-amber-800 mt-2', 'Has overdue item(s). For information only; borrowing is not blocked.'));
        }

        if (d.loans.length) {
            const ul = el('ul', 'mt-2 space-y-1 text-xs text-slate-600 list-disc pl-4');
            d.loans.forEach(function (l) {
                const due = l.overdue ? 'due ' + l.due_date + ' (overdue ' + l.days_overdue + 'd)' : 'due ' + l.due_date;
                ul.append(el('li', l.overdue ? 'text-amber-800' : '', l.title + ' — ' + due));
            });
            nodes.push(ul);
            if (d.active_loans_count > d.loans.length) {
                nodes.push(el('p', 'text-xs text-slate-500 mt-1', '+ ' + (d.active_loans_count - d.loans.length) + ' more'));
            }
        }

        const profileLine = el('p', 'mt-3 text-xs');
        profileLine.append(link('Open full profile ↗', d.profile_url, true));
        nodes.push(profileLine);
        paint(patronBox, tone, nodes);
    }

    function renderBook(d) {
        if (!d.found) {
            return paint(bookBox, 'bad', [
                el('p', 'font-bold text-rose-700', 'No book found'),
                el('p', 'text-xs text-slate-600 mt-1', 'Check the accession number.'),
            ]);
        }

        const nodes = [];
        let tone = d.is_available ? 'ok' : (mode === 'issue' ? 'bad' : 'warn');
        if (d.patron_has_loan) tone = 'warn';

        const head = el('div', 'flex items-start justify-between gap-2');
        const title = el('div');
        title.append(
            el('p', 'font-bold text-slate-900 text-base', d.title + (d.subtitle ? ': ' + d.subtitle : '')),
            el('p', 'text-xs text-slate-600', d.author)
        );
        head.append(title, d.is_available
            ? pill(d.available_copies + ' of ' + d.total_copies + ' available', 'bg-emerald-100 text-emerald-700')
            : pill('None available (0 of ' + d.total_copies + ')', 'bg-rose-100 text-rose-700'));
        nodes.push(head);

        const meta = [d.accession_number, d.call_number, d.library, d.location].filter(Boolean).join(' · ');
        if (meta) nodes.push(el('p', 'text-xs text-slate-500 mt-2', meta));

        if (mode === 'issue' && !d.is_available) {
            nodes.push(el('p', 'text-xs font-semibold text-rose-700 mt-2', 'No copy on the shelf: it cannot be checked out right now.'));
        }
        if (mode === 'reserve' && d.is_available) {
            nodes.push(el('p', 'text-xs text-emerald-800 mt-2', 'A copy is on the shelf right now; you can check it out directly instead.'));
        }
        if (mode === 'reserve' && !d.is_available) {
            nodes.push(el('p', 'text-xs text-amber-800 mt-2', 'All copies are on loan; the reservation will wait in the queue.'));
        }

        if (d.patron_has_loan) {
            nodes.push(el('p', 'text-xs font-semibold text-amber-800 mt-2', 'This patron already has this book on loan.'));
        }

        if (d.pending_reservations_count > 0) {
            let line = d.pending_reservations_count + ' pending reservation' + (d.pending_reservations_count > 1 ? 's' : '');
            if (d.next_in_queue) line += ' · next in line: ' + d.next_in_queue.name;
            nodes.push(el('p', 'text-xs text-indigo-800 mt-2', line));
        }
        if (d.patron_reservation_position) {
            const p = el('p', 'text-xs text-indigo-800 mt-1', 'This patron is #' + d.patron_reservation_position + ' in the reservation queue.');
            if (mode === 'issue') p.append(' Mark it Fulfilled after checking out.');
            nodes.push(p);
        }

        const actions = el('p', 'mt-3 text-xs flex flex-wrap gap-x-4 gap-y-1');
        actions.append(link('View book ↗', d.show_url, true));
        const q = new URLSearchParams({ accession_number: d.accession_number });
        if (patronInput.value.trim()) q.set('patron_id_number', patronInput.value.trim());
        if (mode === 'issue' && !d.is_available) {
            actions.append(link('Reserve this book instead →', root.dataset.reserveUrl + '?' + q, false));
        }
        if (mode === 'reserve' && d.is_available) {
            actions.append(link('Check out now →', root.dataset.issueUrl + '?' + q, false));
        }
        nodes.push(actions);

        paint(bookBox, tone, nodes);
    }

    const state = {
        patron: { timer: null, ctrl: null, seq: 0 },
        book:   { timer: null, ctrl: null, seq: 0 },
    };

    function load(kind, immediate) {
        const s     = state[kind];
        const input = kind === 'patron' ? patronInput : bookInput;
        const box   = kind === 'patron' ? patronBox : bookBox;
        const value = input.value.trim();

        clearTimeout(s.timer);
        if (s.ctrl) s.ctrl.abort();
        const ticket = ++s.seq;           // any older in-flight response is now stale

        if (value === '') { idle(box, kind); return; }

        s.timer = setTimeout(async function () {
            s.ctrl = new AbortController();
            paint(box, 'load', [el('p', '', 'Looking up…')]);

            const params = new URLSearchParams(
                kind === 'patron'
                    ? { id_number: value }
                    : { accession_number: value, patron_id_number: patronInput.value.trim() }
            );

            try {
                const res = await fetch(root.dataset[kind + 'Url'] + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: s.ctrl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (ticket !== s.seq) return;
                kind === 'patron' ? renderPatron(data) : renderBook(data);
            } catch (e) {
                if (e.name === 'AbortError' || ticket !== s.seq) return;
                paint(box, 'idle', [el('p', '', 'Preview unavailable. You can still submit the form.')]);
            }
        }, immediate ? 0 : 300);
    }

    patronInput.addEventListener('input', function () {
        load('patron', false);
        if (bookInput.value.trim() !== '') load('book', false);   // reservation info depends on the patron
    });
    bookInput.addEventListener('input', function () { load('book', false); });

    // Scanner-friendly: scanners "press Enter" after the code. Enter in the patron field
    // jumps to the book field instead of submitting a half-filled form.
    patronInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && patronInput.value.trim() !== '') {
            e.preventDefault();
            load('patron', true);
            bookInput.focus();
        }
    });

    idle(patronBox, 'patron');
    idle(bookBox, 'book');
    if (patronInput.value.trim() !== '') load('patron', true);
    if (bookInput.value.trim() !== '')   load('book', true);
})();
</script>
