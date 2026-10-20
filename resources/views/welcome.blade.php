<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts._head')
    <title>{{ config('app.name', 'SALRC Library') }} — Saint Augustine Learning Resource Center</title>
</head>
<body class="salrc">
<main class="landing">

    <nav class="landing-nav">
        <a href="{{ url('/') }}" class="landing-brand">
            <div class="brand-mark"><x-icon name="library" :size="20" /></div>
            <div>
                <strong>SALRC</strong>
                <small>Library System</small>
            </div>
        </a>

        @auth
            <a class="btn btn-primary" href="{{ route('dashboard') }}">Open dashboard <x-icon name="arrow" :size="15" /></a>
        @else
            <a class="btn btn-primary" href="{{ route('login') }}">Staff sign in <x-icon name="arrow" :size="15" /></a>
        @endauth
    </nav>

    <section class="landing-hero">
        <div>
            <div class="hero-kicker"><span class="kicker-line"></span>Saint Augustine Learning Resource Center</div>
            <h1 class="hero-title">Know where<br><em>every book</em><br>is.</h1>
            <p class="hero-copy">The SALRC library system keeps the catalog, the circulation desk and the daily visitor log in one place, so the library team can spend less time searching and more time helping.</p>
            <div class="hero-cta">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Open dashboard <x-icon name="arrow" :size="15" /></a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Enter staff workspace <x-icon name="arrow" :size="15" /></a>
                @endauth
                <span class="hero-note">For librarians and school staff</span>
            </div>
        </div>

        <div class="hero-art" role="img" aria-label="Illustration of a stack of library books">
            <div class="orbit"></div>
            <div class="book-stack" aria-hidden="true">
                <div class="book">STORIES &amp; LANGUAGE</div>
                <div class="book">SCIENCE IN PRACTICE</div>
                <div class="book">THE ART OF INQUIRY</div>
                <div class="book">LOCAL KNOWLEDGE</div>
            </div>
            <div class="hero-stamp" aria-hidden="true">
                <div><x-icon name="open-book" :size="21" /><span>OPEN<br>SHELVES<br>OPEN MINDS</span></div>
            </div>
        </div>
    </section>

    <section class="landing-strip">
        <div class="strip-inner">
            <div class="strip-item">
                <x-icon name="books" :size="21" class="strip-icon" />
                <div>
                    <strong>One clear catalog</strong>
                    <span>Find any title by author, subject, call number, ISBN or accession number.</span>
                </div>
            </div>
            <div class="strip-item">
                <x-icon name="clipboard" :size="21" class="strip-icon" />
                <div>
                    <strong>Circulation at the desk</strong>
                    <span>Issue, renew and return books, with overdue loans flagged for follow-up.</span>
                </div>
            </div>
            <div class="strip-item">
                <x-icon name="users" :size="21" class="strip-icon" />
                <div>
                    <strong>Visits and reservations</strong>
                    <span>Log who comes in each day and hold copies for the patrons waiting on them.</span>
                </div>
            </div>
        </div>
    </section>

    <footer class="landing-bottom">
        <span>SAINT AUGUSTINE LEARNING RESOURCE CENTER</span>
        <span>Knowledge, cared for and shared.</span>
        <span>Staff portal · Secure access</span>
    </footer>
</main>
</body>
</html>
