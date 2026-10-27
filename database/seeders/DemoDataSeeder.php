<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Patron;
use Illuminate\Database\Seeder;

/**
 * TEST DATA ONLY (for demos / the teacher's presentation).
 *
 * Adds sample books and sample visitor check-ins so the dashboard has something to show.
 * Safe to run again: books are matched by accession number (TEST-xxxxx) and the sample
 * check-ins (remarks = "Test data") are replaced, never duplicated.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * To remove it later:
 *   php artisan tinker
 *   >>> App\Models\Attendance::where('remarks', 'Test data')->delete();
 *   >>> App\Models\Book::where('accession_number', 'like', 'TEST-%')->doesntHave('loans')->delete();
 */
class DemoDataSeeder extends Seeder
{
    public const TAG = 'Test data';

    /** How many sample titles the catalog should have in total (change this number if you like). */
    public const TOTAL_BOOKS = 17000;

    public function run(): void
    {
        $this->books();
        $this->visits();
    }

    private function books(): void
    {
        $books = [
            ['Noli Me Tangere',                  'José Rizal',                    'Filipiniana',      'FIL 899.2113 R59', 'Filipino',  'Philippines -- Fiction; Colonial period', 'Novel'],
            ['El Filibusterismo',                'José Rizal',                    'Filipiniana',      'FIL 899.2113 R59', 'Filipino',  'Philippines -- Fiction; Colonial period', 'Novel'],
            ['Florante at Laura',                'Francisco Balagtas',            'Filipiniana',      'FIL 899.2111 B18', 'Filipino',  'Filipino poetry; Epic poetry',            'Poetry'],
            ['Philippine History',               'Gregorio F. Zaide',             'History',          '959.9 Z18',        'English',   'Philippines -- History',                  'History'],
            ['Confessions',                      'Saint Augustine',               'Religion',         '270.2 A92',        'English',   'Christian saints; Autobiography',         'Autobiography'],
            ['The City of God',                  'Saint Augustine',               'Religion',         '230 A92',          'English',   'Christian theology; Church history',      'Theology'],
            ['Introduction to Algorithms',       'Thomas H. Cormen et al.',       'Computer Science', '005.1 C81',        'English',   'Computer algorithms; Data structures',    'Textbook'],
            ['Campbell Biology',                 'Lisa A. Urry et al.',           'Science',          '570 U78',          'English',   'Biology',                                 'Textbook'],
            ['Fundamentals of Physics',          'David Halliday; Robert Resnick','Science',          '530 H18',          'English',   'Physics',                                 'Textbook'],
            ['Calculus',                         'James Stewart',                 'Mathematics',      '515 S84',          'English',   'Calculus',                                'Textbook'],
            ['Principles of Economics',          'N. Gregory Mankiw',             'Social Science',   '330 M31',          'English',   'Economics',                               'Textbook'],
            ['Hamlet',                           'William Shakespeare',           'Literature',       '822.33 S52',       'English',   'Tragedies; English drama',                'Drama'],
            ['To Kill a Mockingbird',            'Harper Lee',                    'Fiction',          'FIC L477',         'English',   'Race relations -- Fiction',               'Novel'],
            ['The Little Prince',                'Antoine de Saint-Exupéry',      'Fiction',          'FIC S14',          'English',   'Children\'s stories; Friendship',         'Fable'],
            ['The Diary of a Young Girl',        'Anne Frank',                    'Biography',        '940.53 F82',       'English',   'World War, 1939-1945; Diaries',           'Diary'],
        ];

        // An earlier version of this seeder numbered the first 15 as TEST-0001 ...: keep them, just renumber.
        foreach (range(1, count($books)) as $n) {
            $old = sprintf('TEST-%04d', $n);
            $new = sprintf('TEST-%05d', $n);

            if (! Book::where('accession_number', $new)->exists()) {
                Book::where('accession_number', $old)->update(['accession_number' => $new]);
            }
        }

        foreach ($books as $i => [$title, $author, $category, $call, $language, $subjects, $genre]) {
            $copies = 2 + ($i % 4); // 2 to 5 copies

            Book::firstOrCreate(
                ['accession_number' => sprintf('TEST-%05d', $i + 1)],
                [
                    'title'            => $title,
                    'author'           => $author,
                    'category'         => $category,
                    'call_number'      => $call,
                    'language'         => $language,
                    'subjects'         => $subjects,
                    'genre'            => $genre,
                    'content_type'     => 'text',
                    'media_type'       => 'unmediated',
                    'carrier_type'     => 'volume',
                    'total_copies'     => $copies,
                    'available_copies' => $copies,
                    'notes'            => self::TAG,
                ]
            );
        }

        $this->fillCatalog(count($books) + 1);
    }

    /**
     * Fill the catalog up to TOTAL_BOOKS titles (TEST-00016 ...) so the numbers look like a real library.
     * Titles and authors are made up. insertOrIgnore + the unique accession number = safe to run again.
     */
    private function fillCatalog(int $from): void
    {
        $areas = [
            // category => [dewey class, [topics]]
            'Science'          => ['500', ['Biology', 'Chemistry', 'Physics', 'Earth Science', 'Astronomy', 'Ecology', 'Genetics', 'Environmental Science']],
            'Mathematics'      => ['510', ['Algebra', 'Geometry', 'Trigonometry', 'Statistics', 'Calculus', 'Discrete Mathematics', 'Probability']],
            'Computer Science' => ['004', ['Programming', 'Databases', 'Computer Networks', 'Web Development', 'Operating Systems', 'Information Systems', 'Software Engineering']],
            'Social Science'   => ['300', ['Sociology', 'Economics', 'Political Science', 'Psychology', 'Anthropology', 'Philippine Government', 'Research Methods']],
            'Education'        => ['370', ['Teaching Strategies', 'Curriculum Development', 'Classroom Management', 'Assessment of Learning', 'Special Education', 'Guidance and Counseling']],
            'Business'         => ['650', ['Accounting', 'Marketing', 'Management', 'Entrepreneurship', 'Business Law', 'Human Resources', 'Financial Management']],
            'Literature'       => ['800', ['English Literature', 'Philippine Literature', 'World Literature', 'Poetry', 'Short Stories', 'Creative Writing', 'Drama']],
            'History'          => ['900', ['Philippine History', 'World History', 'Asian History', 'Church History', 'Ancient Civilizations', 'Modern History']],
            'Religion'         => ['200', ['Christian Living', 'Sacred Scripture', 'Moral Theology', 'Church Teachings', 'Saints and Their Lives', 'Catholic Social Teaching']],
            'Filipiniana'      => ['FIL', ['Filipino Culture', 'Panitikang Pilipino', 'Philippine Folklore', 'Filipino Language', 'Katutubong Sining', 'Philippine Society']],
            'Health'           => ['610', ['Nursing', 'Nutrition', 'First Aid', 'Public Health', 'Anatomy and Physiology', 'Pharmacology']],
            'Fiction'          => ['FIC', ['Adventure Stories', 'Mystery Stories', 'Young Adult Stories', 'Short Fiction', 'Family Stories']],
            'Reference'        => ['REF', ['Encyclopedia', 'Dictionary', 'Atlas', 'Handbook', 'Almanac', 'Directory']],
        ];

        $patterns = [
            'Introduction to %s', 'Fundamentals of %s', '%s: Principles and Practice', 'Essentials of %s',
            'Modern %s', 'Understanding %s', '%s for Beginners', 'Advanced %s', '%s: A Practical Guide',
            'Foundations of %s', 'Exploring %s', '%s Today',
            'The Story of %s', 'The Art of %s', 'The Complete Guide to %s', 'The World of %s',
        ];

        $surnames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Villanueva',
            'Ramos', 'Aquino', 'Castillo', 'Navarro', 'Domingo', 'Salazar', 'Pascual', 'Lim', 'Tan', 'Gonzales',
            'Smith', 'Johnson', 'Brown', 'Miller', 'Davis', 'Wilson', 'Anderson', 'Taylor', 'Clark', 'Walker'];
        $initials = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'J.', 'L.', 'M.', 'P.', 'R.', 'S.', 'T.'];

        $keys  = array_keys($areas);
        $now   = now();
        $rows  = [];
        $total = self::TOTAL_BOOKS;

        $flush = function () use (&$rows) {
            if ($rows) {
                Book::insertOrIgnore($rows);
                $rows = [];
            }
        };

        for ($n = $from; $n <= $total; $n++) {
            mt_srand($n); // same book for the same number every time

            $category = $keys[$n % count($keys)];
            [$dewey, $topics] = $areas[$category];
            $topic    = $topics[mt_rand(0, count($topics) - 1)];
            $title    = sprintf($patterns[mt_rand(0, count($patterns) - 1)], $topic);
            $edition  = mt_rand(1, 6);
            $surname  = $surnames[mt_rand(0, count($surnames) - 1)];
            $author   = $initials[mt_rand(0, count($initials) - 1)] . ' ' . $surname;
            $copies   = mt_rand(1, 5);

            $rows[] = [
                'accession_number'    => sprintf('TEST-%05d', $n),
                'title'               => $title,
                'author'              => $author,
                'category'            => $category,
                'call_number'         => $dewey . '.' . mt_rand(1, 99) . ' ' . $surname[0] . mt_rand(10, 99),
                'edition_statement'   => $edition === 1 ? null : $this->ordinal($edition) . ' edition',
                'year_of_publication' => (string) mt_rand(1998, 2025),
                'language'            => $category === 'Filipiniana' ? 'Filipino' : 'English',
                'subjects'            => $topic,
                'content_type'        => 'text',
                'media_type'          => 'unmediated',
                'carrier_type'        => 'volume',
                'total_copies'        => $copies,
                'available_copies'    => $copies,
                'notes'               => self::TAG,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            if (count($rows) >= 200) {
                $flush();
            }
        }

        $flush();
        mt_srand(); // back to normal randomness
    }

    private function ordinal(int $n): string
    {
        return ['', '1st', '2nd', '3rd', '4th', '5th', '6th'][$n] ?? $n . 'th';
    }

    private function visits(): void
    {
        if (! Patron::query()->exists()) {
            $this->call(PatronSeeder::class);
        }

        $patrons = Patron::where('status', 'active')->inRandomOrder()->take(12)->get();

        if ($patrons->isEmpty()) {
            return;
        }

        Attendance::where('remarks', self::TAG)->delete();

        // Today: random times between 7:30 AM and now (or since midnight if it is earlier than that).
        $start = today()->setTime(7, 30);
        $end   = now();

        if ($end->lte($start)) {
            $start = today();
        }

        $span  = max(1, (int) $end->diffInSeconds($start, true));
        $times = $patrons->map(fn () => $start->copy()->addSeconds(random_int(0, $span)))->sort()->values();

        foreach ($patrons as $i => $patron) {
            Attendance::create([
                'patron_id'  => $patron->id,
                'visited_at' => $times[$i],
                'remarks'    => self::TAG,
            ]);
        }

        // The previous 6 days, so the Statistics page has something to show too.
        foreach (range(1, 6) as $daysAgo) {
            foreach ($patrons->random(min($patrons->count(), random_int(4, 10))) as $patron) {
                Attendance::create([
                    'patron_id'  => $patron->id,
                    'visited_at' => today()->subDays($daysAgo)->setTime(random_int(8, 16), random_int(0, 59)),
                    'remarks'    => self::TAG,
                ]);
            }
        }
    }
}
