<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Holiday;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * SALRC Library Assistant: a rule-based chatbot (no external AI service, no API key).
 *
 * - Understands English and Filipino / Taglish keywords.
 * - Answers from the library's own data: catalog search, a signed-in patron's own loans
 *   and reservations, borrow limits, loan period, holidays.
 * - Follows BUSINESS_RULES.md: it never mentions fine amounts (fines are settled offline
 *   with the librarian), never changes any record (read only), never shows accession
 *   numbers, and only shows loan data of the signed-in patron.
 */
class LibraryChatbot
{
    /** Words that mark a message as Filipino / Taglish, so the bot answers in Taglish. */
    private const FILIPINO = [
        'po', 'ba', 'mga', 'libro', 'aklat', 'hiram', 'hiramin', 'humiram', 'hiniram', 'nahiram', 'pahiram',
        'pwede', 'pwedeng', 'magkano', 'paano', 'pano', 'saan', 'kailan', 'kelan', 'ilan', 'ilang', 'araw',
        'kumusta', 'kamusta', 'musta', 'salamat', 'magandang', 'umaga', 'hapon', 'gabi', 'oras', 'bukas',
        'sarado', 'ano', 'meron', 'mayroon', 'gusto', 'hanapin', 'hanap', 'pahanap', 'pakihanap', 'nasaan',
        'yung', 'ung', 'naman', 'ko', 'akin', 'ako', 'sino', 'kayo', 'ninyo', 'nyo', 'sige', 'hindi', 'wala',
        'paki', 'isauli', 'ibalik', 'nawala', 'nasira', 'multa', 'bayad', 'tungkol', 'kailangan', 'tulong',
        'paalam', 'lagpas', 'reserba', 'pareserve', 'palawigin', 'pahabain', 'walang', 'pasok', 'dito',
        'magrehistro', 'nakalimutan', 'katagal', 'gaano', 'katagal',
    ];

    /** Filler words dropped from a book search (every remaining word must match). */
    private const STOPWORDS = [
        'can', 'you', 'could', 'please', 'pls', 'paki', 'pakihanap', 'help', 'me', 'do', 'does', 'have', 'got',
        'is', 'there', 'are', 'a', 'an', 'the', 'of', 'book', 'books', 'libro', 'aklat', 'title', 'titles',
        'about', 'on', 'by', 'tungkol', 'sa', 'ni', 'ng', 'ang', 'yung', 'ung', 'po', 'ba', 'kayo', 'meron',
        'mayroon', 'may', 'search', 'find', 'look', 'looking', 'for', 'hanapin', 'hanap', 'pahanap', 'i',
        'need', 'want', 'gusto', 'ko', 'kailangan', 'available', 'copy', 'copies', 'any', 'some', 'in',
        'library', 'naman', 'dito', 'show', 'list', 'to', 'at', 'with', 'my', 'kopya', 'ito', 'iyon',
        'what', 'which', 'where', 'whats', "what's", 'anong', 'ano', 'na',
        'saan', 'nasaan', 'nasan', 'mahahanap', 'makikita', 'makukuha', 'nakalagay', 'location', 'locate', 'located', 'shelf', 'lugar',
    ];

    private ?User $user = null;
    private ?Patron $patron = null;
    private bool $tl = false;

    public function __construct(private LibrarySettings $settings)
    {
    }

    /**
     * @return array{reply:string, books:array<int,array<string,mixed>>, links:array<int,array<string,string>>, suggestions:array<int,string>}
     */
    public function reply(string $message, ?User $user = null): array
    {
        $this->user = $user;
        $this->patron = ($user && $user->is_active && $user->isPatron()) ? $user->patron : null;

        $lower = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $message)));
        $words = $this->words($lower);
        $this->tl = count(array_intersect($words, self::FILIPINO)) > 0;
        $count = count($words);

        if ($count === 0) {
            return $this->fallback();
        }

        if ($count <= 5 && $this->any($lower, $words, ['hi', 'hello', 'hey', 'hola', 'kumusta', 'kamusta', 'musta',
            'good morning', 'good afternoon', 'good evening', 'magandang umaga', 'magandang hapon', 'magandang gabi', 'magandang araw'])) {
            return $this->out($this->t(
                "Hi! I'm the SALRC Library Assistant. I can search the catalog, show your due dates and answer questions about borrowing. What do you need?",
                'Hello! Ako ang SALRC Library Assistant. Pwede akong maghanap ng libro, magpakita ng due dates mo, at sumagot ng tanong tungkol sa paghiram. Ano ang maitutulong ko?'
            ));
        }

        if ($count <= 6 && $this->any($lower, $words, ['thanks', 'thank', 'thankyou', 'salamat'])) {
            return $this->out($this->t('You are welcome! Anything else I can help with?', 'Walang anuman! May iba pa ba akong maitutulong?'));
        }

        if ($count <= 3 && $this->any($lower, $words, ['bye', 'goodbye', 'paalam'])) {
            return $this->out($this->t('Goodbye! Happy reading.', 'Paalam! Happy reading!'));
        }

        if ($count <= 4 && $this->any($lower, $words, ['help', 'menu', 'options', 'commands', 'tulong', 'what can you do'])) {
            return $this->help();
        }

        if ($this->any($lower, $words, ['holiday', 'holidays', 'suspension', 'suspensions', 'suspended', 'walang pasok'])) {
            return $this->holidays();
        }

        if ($this->any($lower, $words, ['hours', 'hour', 'opening', 'closing', 'open', 'closed', 'close', 'schedule', 'oras', 'bukas', 'sarado', 'contact', 'phone', 'email', 'address'])) {
            return $this->hours();
        }

        if ($this->any($lower, $words, ['fine', 'fines', 'penalty', 'penalties', 'multa', 'bayad', 'payment', 'pay', 'lost', 'damaged', 'nawala', 'nasira'])) {
            return $this->fines();
        }

        if ($this->any($lower, $words, ['renew', 'renewal', 'renewing', 'extend', 'extension', 'palawigin', 'pahabain', 'irenew', 'i-renew'])) {
            return $this->renew();
        }

        if ($this->any($lower, $words, ['reserve', 'reserved', 'reservation', 'reservations', 'hold', 'reserba', 'ireserve', 'i-reserve', 'pareserve', 'nireserba'])) {
            $personal = $this->any($lower, $words, ['my', 'ko', 'akin', 'status', 'nireserba', 'meron ba akong']);

            return $personal ? $this->myReservations() : $this->howToReserve();
        }

        if ($this->any($lower, $words, ['due', 'overdue', 'lagpas', 'nahiram', 'hiniram', 'borrowed', 'my loans', 'my loan', 'my books',
            'hiram ko', 'libro ko', 'kailan isauli', 'kailan ibalik', 'when do i return', 'when to return', 'what did i borrow'])) {
            return $this->myLoans();
        }

        if ($this->any($lower, $words, ['how long', 'how many days', 'ilang araw', 'gaano katagal', 'loan period', 'days to borrow'])) {
            return $this->loanPeriod();
        }

        if ($this->any($lower, $words, ['limit', 'maximum', 'max', 'how many', 'ilan', 'ilang'])
            && $this->any($lower, $words, ['borrow', 'hiram', 'humiram', 'hiramin', 'can', 'allowed', 'pwede', 'pwedeng', 'loan', 'loans'])) {
            return $this->borrowLimit();
        }

        if ($this->any($lower, $words, ['borrow', 'hiram', 'humiram', 'hiramin', 'pahiram'])
            && $this->any($lower, $words, ['how', 'paano', 'pano', 'process', 'steps', 'requirements', 'requirement', 'need', 'kailangan'])) {
            return $this->howToBorrow();
        }

        if ($this->any($lower, $words, ['account', 'login', 'log in', 'sign in', 'password', 'register', 'registration', 'magrehistro', 'nakalimutan', 'forgot'])) {
            return $this->account();
        }

        $explicitSearch = $this->any($lower, $words, ['search', 'find', 'look', 'looking', 'hanap', 'hanapin', 'pahanap', 'pakihanap',
            'meron', 'mayroon', 'available', 'do you have', 'have you', 'books about', 'book about', 'libro tungkol', 'may libro',
            'saan', 'nasaan', 'nasan', 'mahahanap', 'makikita', 'makukuha', 'nakalagay', 'location', 'locate', 'located', 'where']);

        if ($explicitSearch || $count <= 6) {
            return $this->search($words, ! $explicitSearch);
        }

        return $this->fallback();
    }

    /* ---------------------------------------------------------------
     | Answers
     |---------------------------------------------------------------*/

    private function help(): array
    {
        return $this->out($this->t(
            "Here is what I can do:\n• Search the catalog (type a title, author or subject)\n• Show your books on loan and due dates (when signed in)\n• Show your reservations (when signed in)\n• Explain how to borrow, renew and reserve\n• Borrow limits, loan period, holidays and library hours",
            "Ito ang kaya kong gawin:\n• Maghanap ng libro (i-type ang title, author o subject)\n• Ipakita ang mga hiniram mo at due dates (kapag naka-sign in)\n• Ipakita ang mga reservation mo (kapag naka-sign in)\n• Ipaliwanag kung paano humiram, mag-renew at mag-reserve\n• Borrow limit, ilang araw ang hiram, holidays at oras ng library"
        ));
    }

    private function search(array $words, bool $guess): array
    {
        $tokens = array_values(array_filter(
            $words,
            fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, self::STOPWORDS, true)
        ));

        if ($tokens === []) {
            return $guess
                ? $this->fallback()
                : $this->out($this->t(
                    'Sure! Type the title, author or subject you are looking for, for example "Noli Me Tangere" or "Rizal".',
                    'Sige! I-type ang title, author o subject na hinahanap mo, halimbawa "Noli Me Tangere" o "Rizal".'
                ));
        }

        $cols = ['title', 'subtitle', 'author', 'isbn', 'call_number', 'subjects', 'category', 'publisher'];

        $query = Book::query();
        foreach ($tokens as $token) {
            $like = '%' . addcslashes($token, '%_\\') . '%';
            $query->where(function ($w) use ($cols, $like) {
                foreach ($cols as $col) {
                    $w->orWhere($col, 'like', $like);
                }
            });
        }

        $total = (clone $query)->count();
        $label = implode(' ', $tokens);

        if ($total === 0) {
            // Maybe a typo: offer the closest titles in the catalog.
            $similar = $this->similarTitles($tokens);

            if ($similar !== []) {
                $list = '• ' . implode("\n• ", $similar);

                return $this->out($this->t(
                    "I couldn't find \"{$label}\". Did you mean:\n{$list}\nType the full title to see where it is.",
                    "Hindi ko nakita ang \"{$label}\". Ito ba ang ibig mong sabihin:\n{$list}\nI-type ang buong title para makita ang location."
                ));
            }

            return $guess
                ? $this->fallback()
                : $this->out($this->t(
                    "I couldn't find a book matching \"{$label}\" in the catalog. Try another title, author or subject.",
                    "Wala sa catalog ang libro na \"{$label}\". Subukan ang ibang title, author o subject."
                ));
        }

        $found = $query->orderBy('title')->limit(5)->get();

        $books = $found->map(fn (Book $b) => [
            'title'     => trim($b->title . ($b->subtitle ? ': ' . $b->subtitle : '')),
            'author'    => $b->author ?: null,
            'where'     => implode(' · ', array_filter([
                $b->call_number ? 'Call no. ' . $b->call_number : null,
                $b->library ?: null,
                $b->location ?: null,
            ])),
            'available' => $b->available_copies > 0,
            'status'    => $b->available_copies > 0
                ? $this->t("Available: {$b->available_copies} of {$b->total_copies}", "Available: {$b->available_copies} sa {$b->total_copies}")
                : $this->t('All copies on loan', 'Lahat ng kopya ay hiram ngayon'),
        ])->all();

        $reply = $total === 1
            ? $this->t('I found 1 book:', 'May nakita akong 1 libro:')
            : $this->t("I found {$total} books" . ($total > 5 ? ' (showing the first 5):' : ':'),
                       "May nakita akong {$total} na libro" . ($total > 5 ? ' (ito ang unang 5):' : ':'));

        $links = $total > 5
            ? [['label' => $this->t('See all results in the catalog', 'Tingnan lahat sa catalog'), 'url' => route('catalog.index', ['q' => $label], false)]]
            : [];

        return $this->out($reply, $books, $links);
    }

    /**
     * Misspelling help: the catalog titles closest to what the student typed
     * ("acounting" finds "The Art of Accounting"). A word counts as a match when it is the same,
     * or within 1 letter (2 for long words) of a word in the title. Needs 60%+ of the typed words to match.
     *
     * @param  array<int,string>  $tokens
     * @return array<int,string>
     */
    private function similarTitles(array $tokens, int $limit = 3): array
    {
        $tokens = array_values(array_filter(array_map(fn ($t) => Str::ascii(mb_strtolower($t)), $tokens), fn ($t) => strlen($t) >= 3));

        if ($tokens === []) {
            return [];
        }

        // All distinct titles, remembered for 10 minutes so the check stays fast.
        $titles = Cache::remember('chatbot.titles', 600, fn () => Book::query()->whereNotNull('title')->distinct()->pluck('title')->all());

        $scored = [];

        foreach ($titles as $title) {
            $titleWords = preg_split('/[^a-z0-9]+/', Str::ascii(mb_strtolower($title)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $hits = 0;

            foreach ($tokens as $token) {
                $allowed = strlen($token) >= 7 ? 2 : (strlen($token) >= 4 ? 1 : 0);

                foreach ($titleWords as $word) {
                    if ($token === $word || ($allowed > 0 && levenshtein($token, $word) <= $allowed)) {
                        $hits++;
                        break;
                    }
                }
            }

            $score = $hits / count($tokens);

            if ($score >= 0.6) {
                $scored[$title] = $score - (abs(count($titleWords) - count($tokens)) * 0.001); // prefer closer length
            }
        }

        arsort($scored);

        return array_slice(array_keys($scored), 0, $limit);
    }

    private function myLoans(): array
    {
        if (! $this->patron) {
            return $this->needSignIn();
        }

        $active = Loan::with('book')->where('patron_id', $this->patron->id)->active()->orderBy('due_date')->get();

        if ($active->isEmpty()) {
            return $this->out($this->t('You have no books on loan right now.', 'Wala ka pang hiniram na libro sa ngayon.'));
        }

        $n = $active->count();
        $lines = [$this->t(
            "You have {$n} " . ($n === 1 ? 'book' : 'books') . ' on loan:',
            "May {$n} kang hiniram na libro:"
        )];

        $anyOverdue = false;

        foreach ($active as $loan) {
            $due = $loan->due_date->format('M d, Y');
            $left = $this->daysUntil($loan->due_date);

            if ($loan->isOverdue()) {
                $anyOverdue = true;
                $d = $loan->daysOverdue();
                $note = $this->t("{$d} " . ($d === 1 ? 'day' : 'days') . ' overdue', "{$d} araw nang lampas");
            } elseif ($left === 0) {
                $note = $this->t('due today', 'due ngayon');
            } elseif ($left === 1) {
                $note = $this->t('due tomorrow', 'due bukas');
            } else {
                $note = $this->t("{$left} days left", "{$left} araw na lang");
            }

            $title = $loan->book?->title ?? '—';
            $lines[] = "• {$title} — due {$due} ({$note})";
        }

        if ($anyOverdue) {
            $lines[] = $this->t(
                'Please return overdue books to the library. Any fine is settled in person with the librarian.',
                'Pakibalik na sa library ang mga lagpas na sa due date. Ang anumang multa ay inaayos nang personal sa librarian.'
            );
        }

        return $this->out(implode("\n", $lines));
    }

    private function myReservations(): array
    {
        if (! $this->patron) {
            return $this->needSignIn();
        }

        $reservations = Reservation::with('book')->where('patron_id', $this->patron->id)
            ->pending()->orderBy('reserved_at')->get();

        if ($reservations->isEmpty()) {
            return $this->out($this->t('You have no pending reservations.', 'Wala kang pending na reservation.'));
        }

        $positions = Reservation::queuePositions($reservations);
        $n = $reservations->count();

        $lines = [$this->t(
            "You have {$n} pending " . ($n === 1 ? 'reservation' : 'reservations') . ':',
            "May {$n} kang pending na reservation:"
        )];

        foreach ($reservations as $r) {
            $pos = $positions[$r->id] ?? 1;
            $lines[] = '• ' . ($r->book?->title ?? '—') . ' — ' . $this->t("queue no. {$pos}", "pila no. {$pos}");
        }

        return $this->out(implode("\n", $lines));
    }

    private function howToReserve(): array
    {
        return $this->out($this->t(
            "To reserve a book, ask the librarian at the library desk. You can reserve a book even when all copies are on loan, and the librarian will set it aside when it is returned.",
            "Para mag-reserve ng libro, magtanong sa librarian sa library desk. Pwede kang mag-reserve kahit hiram ang lahat ng kopya, at itatabi ito ng librarian kapag naibalik na."
        ));
    }

    private function borrowLimit(): array
    {
        if ($this->patron) {
            $max = $this->patron->maxLoans();
            $now = Loan::where('patron_id', $this->patron->id)->active()->count();
            $type = $this->patron->patron_type;

            return $this->out($this->t(
                "As a {$type}, you can borrow up to {$max} books at a time. You currently have {$now} on loan.",
                "Bilang {$type}, pwede kang humiram ng hanggang {$max} libro nang sabay. Sa ngayon, {$now} ang hiram mo."
            ));
        }

        $parts = array_map(fn ($type) => $type . ': ' . $this->settings->maxLoans($type), Patron::TYPES);

        return $this->out($this->t(
            'Books you can borrow at the same time — ' . implode(', ', $parts) . '.',
            'Ilang libro ang pwedeng hiramin nang sabay — ' . implode(', ', $parts) . '.'
        ));
    }

    private function loanPeriod(): array
    {
        $days = $this->settings->loanDays();

        return $this->out($this->t(
            "Books are normally lent for {$days} days. The librarian may set a different period when issuing a book.",
            "Karaniwang {$days} araw ang hiram ng libro. Maaaring magtakda ang librarian ng ibang bilang ng araw kapag ini-issue ang libro."
        ));
    }

    private function howToBorrow(): array
    {
        return $this->out($this->t(
            "How to borrow:\n1. Find the book in the catalog and note its call number.\n2. Get it from the shelf and bring it, with your ID, to the circulation desk.\n3. The librarian will issue it to you.\nYou must be an active patron, a copy must be available, you must be under your borrow limit, and you cannot borrow a title you already have on loan.",
            "Paano humiram:\n1. Hanapin ang libro sa catalog at tandaan ang call number.\n2. Kunin ito sa shelf at dalhin, kasama ang ID mo, sa circulation desk.\n3. I-issue ito sa iyo ng librarian.\nKailangang active patron ka, may available na kopya, hindi ka lalampas sa borrow limit mo, at hindi ka pwedeng humiram ng title na hawak mo na."
        ));
    }

    private function renew(): array
    {
        $reply = $this->t(
            'To renew a book, bring it to the library desk and the librarian will extend the due date. Renewing is done in person; I can only show information.',
            'Para mag-renew ng libro, dalhin ito sa library desk at ie-extend ng librarian ang due date. Personal itong ginagawa; impormasyon lang ang maibibigay ko.'
        );

        $max = config('library.max_renewals');
        if ($max !== null) {
            $reply .= ' ' . $this->t("Each loan can be renewed up to {$max} times.", "Hanggang {$max} beses puwedeng i-renew ang bawat hiram.");
        }

        return $this->out($reply);
    }

    private function fines(): array
    {
        return $this->out($this->t(
            "This system does not compute or collect fines. Any fine, and any replacement for a lost or damaged book, is settled in person with the librarian.",
            "Hindi nagko-compute o tumatanggap ng multa ang system na ito. Ang anumang multa, at ang kapalit ng nawala o nasirang libro, ay inaayos nang personal sa librarian."
        ));
    }

    private function hours(): array
    {
        $hours = trim((string) config('library.chatbot.hours'));
        $contact = trim((string) config('library.chatbot.contact'));

        $lines = [];
        $lines[] = $hours !== ''
            ? $this->t("Library hours: {$hours}", "Oras ng library: {$hours}")
            : $this->t(
                "I don't have the library hours on file. Please ask the librarian or check the school announcements.",
                'Wala sa akin ang oras ng library. Magtanong sa librarian o tingnan ang mga anunsyo ng school.'
            );

        if ($contact !== '') {
            $lines[] = "Contact: {$contact}";
        }

        return $this->out(implode("\n", $lines));
    }

    private function holidays(): array
    {
        $upcoming = Holiday::query()->where('date', '>=', today()->toDateString())->orderBy('date')->limit(5)->get();

        if ($upcoming->isEmpty()) {
            return $this->out($this->t(
                'No upcoming holidays or suspensions are recorded in the system. Please check with the library for the latest announcements.',
                'Walang naka-record na paparating na holiday o suspension sa system. Magtanong sa library para sa pinakabagong anunsyo.'
            ));
        }

        $lines = [$this->t('Upcoming holidays / suspensions on record:', 'Mga paparating na holiday / suspension na naka-record:')];
        foreach ($upcoming as $h) {
            $lines[] = '• ' . Carbon::parse($h->date)->format('M d, Y') . ($h->reason ? ' — ' . $h->reason : '');
        }

        return $this->out(implode("\n", $lines));
    }

    private function account(): array
    {
        return $this->out(
            $this->t(
                'Student and faculty accounts are created by the librarian. Forgot your password? On the sign-in page tap \"Forgot your password?\", type your ID number, then choose a new one.',
                'Ang student at faculty account ay ginagawa ng librarian. Nakalimutan ang password? Sa sign-in page, pindutin ang \"Forgot your password?\", i-type ang ID number mo, tapos pumili ng bagong password.'
            ),
            [],
            $this->user ? [] : [['label' => $this->t('Forgot your password?', 'Nakalimutan ang password?'), 'url' => route('password.request', [], false)]]
        );
    }

    private function needSignIn(): array
    {
        if ($this->user) {
            return $this->out($this->t(
                'Personal borrowing records are only shown for student and faculty portal accounts.',
                'Ang personal na borrowing records ay para lang sa student at faculty portal account.'
            ));
        }

        return $this->out(
            $this->t(
                'Please sign in to your student / faculty account so I can show your own records.',
                'Mag-sign in muna sa student / faculty account mo para maipakita ko ang sarili mong records.'
            ),
            [],
            [['label' => $this->t('Sign in', 'Mag-sign in'), 'url' => route('login', [], false)]]
        );
    }

    private function fallback(): array
    {
        return $this->out($this->t(
            "Sorry, I didn't catch that. Try typing a book title, for example \"Where is Noli Me Tangere?\", or ask about due dates, borrowing, renewing, reserving, holidays or library hours.",
            'Pasensya na, hindi ko naintindihan. Subukan mong i-type ang title ng libro, halimbawa \"Saan ang Noli Me Tangere?\", o magtanong tungkol sa due dates, paghiram, pag-renew, pag-reserve, holidays o oras ng library.'
        ));
    }

    /* ---------------------------------------------------------------
     | Helpers
     |---------------------------------------------------------------*/

    private function out(string $reply, array $books = [], array $links = []): array
    {
        return [
            'reply'       => $reply,
            'books'       => $books,
            'links'       => $links,
            'suggestions' => $this->chips(),
        ];
    }

    /** Quick-reply buttons. Each label is also a message the bot understands. */
    public function chips(): array
    {
        if ($this->patron) {
            return $this->tl
                ? ['Hanapin ang libro', 'Due dates ko', 'Reservation ko', 'Paano humiram']
                : ['Find a book', 'My due dates', 'My reservations', 'How to borrow'];
        }

        return $this->tl
            ? ['Hanapin ang libro', 'Paano humiram', 'Oras ng library', 'Multa']
            : ['Find a book', 'How to borrow', 'Library hours', 'Fines'];
    }

    private function t(string $en, string $tl): string
    {
        return $this->tl ? $tl : $en;
    }

    /** @return array<int,string> */
    private function words(string $lower): array
    {
        return preg_split('/[^\p{L}\p{N}\'-]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** True if any single word, or any multi-word phrase, appears in the message. */
    private function any(string $lower, array $words, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($needle, ' ')) {
                if (str_contains($lower, $needle)) {
                    return true;
                }
            } elseif (in_array($needle, $words, true)) {
                return true;
            }
        }

        return false;
    }

    private function daysUntil(CarbonInterface $date): int
    {
        return (int) round(($date->copy()->startOfDay()->getTimestamp() - today()->getTimestamp()) / 86400);
    }
}
