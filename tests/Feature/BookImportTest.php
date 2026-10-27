<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

/**
 * Bulk add books from an Excel (.xlsx) or CSV sheet. Additive only; nothing is stored or generated.
 */
class BookImportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
    }

    private function csv(string $content, string $name = 'books.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_csv_import_adds_books_with_defaults(): void
    {
        $csv = "accession_number,title,author,category,total_copies,available_copies,call_number,year_of_publication,price,date_acquired\n"
            . "ACC-1,Noli Me Tangere,Jose Rizal,Filipiniana,3,2,FIL 899 R59,1887,\"1,250.50\",2024-06-15\n"
            . "ACC-2,No Author No Copies,,,,,,,,\n";

        $this->actingAs($this->user('librarian'))
            ->post(route('books.import.store'), ['file' => $this->csv($csv)])
            ->assertRedirect(route('books.import'))
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 2 && $r['skipped'] === []);

        $noli = Book::where('accession_number', 'ACC-1')->first();
        $this->assertSame(3, $noli->total_copies);
        $this->assertSame(2, $noli->available_copies);
        $this->assertSame('FIL 899 R59', $noli->call_number);
        $this->assertSame('1887', $noli->year_of_publication);
        $this->assertSame('1250.50', (string) $noli->price);
        $this->assertSame('2024-06-15', $noli->date_acquired->toDateString());

        $plain = Book::where('accession_number', 'ACC-2')->first();
        $this->assertSame('Unknown', $plain->author);
        $this->assertSame(1, $plain->total_copies);
        $this->assertSame(1, $plain->available_copies);
        $this->assertNull($plain->category);
    }

    public function test_existing_duplicates_and_bad_rows_are_skipped_without_changing_anything(): void
    {
        Book::create(['accession_number' => 'ACC-1', 'title' => 'Original Title', 'author' => 'A', 'total_copies' => 1, 'available_copies' => 1]);

        $csv = "Accession No,Title,Author,Copies,Available,Price,Date Acquired\n"
            . "ACC-1,Changed Title,X,1,1,,\n"          // already exists
            . "ACC-2,New One,X,2,,,\n"
            . "ACC-2,Same In File,X,1,,,\n"            // duplicate inside the file
            . "ACC-3,,X,1,,,\n"                         // no title
            . "ACC-4,Bad Copies,X,two,,,\n"             // copies not a number
            . "ACC-5,Too Many Available,X,1,5,,\n"      // available > total
            . "ACC-6,Bad Price,X,1,,abc,\n"
            . "ACC-7,Bad Date,X,1,,,2015\n";            // a bare year is not a date

        $this->actingAs($this->user('admin'))
            ->post(route('books.import.store'), ['file' => $this->csv($csv)])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && count($r['skipped']) === 7 && $r['total'] === 8);

        $this->assertSame('Original Title', Book::where('accession_number', 'ACC-1')->value('title'));
        $this->assertSame(2, Book::count());
        $this->assertSame('New One', Book::where('accession_number', 'ACC-2')->value('title'));
    }

    public function test_a_file_without_the_required_headings_is_refused(): void
    {
        $this->actingAs($this->user('librarian'))
            ->post(route('books.import.store'), ['file' => $this->csv("foo,bar\n1,2\n")])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Book::count());
    }

    public function test_an_xlsx_file_can_be_imported_including_excel_dates(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<si><t>Accession Number</t></si><si><t>Title</t></si><si><t>Author</t></si><si><t>Date Acquired</t></si>'
            . '<si><t>XL-1</t></si><si><t>Excel Book</t></si><si><t>Excel Author</t></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            . '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row>'
            . '<row r="2"><c r="A2" t="s"><v>4</v></c><c r="B2" t="s"><v>5</v></c><c r="C2" t="s"><v>6</v></c><c r="D2"><v>45000</v></c></row>'
            . '<row r="3"><c r="A3"><v>2026001</v></c><c r="B3" t="inlineStr"><is><t>Numeric Accession</t></is></c></row>'
            . '</sheetData></worksheet>');
        $zip->close();

        $file = UploadedFile::fake()->createWithContent('books.xlsx', file_get_contents($tmp));
        @unlink($tmp);

        $this->actingAs($this->user('librarian'))
            ->post(route('books.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 2);

        $book = Book::where('accession_number', 'XL-1')->first();
        $this->assertSame('Excel Book', $book->title);
        $this->assertSame('2023-03-15', $book->date_acquired->toDateString()); // Excel date number 45000
        $this->assertSame('Numeric Accession', Book::where('accession_number', '2026001')->value('title'));
    }

    public function test_only_admin_and_librarian_can_import_and_other_formats_are_refused(): void
    {
        $this->actingAs($this->user('staff'))->get(route('books.import'))->assertForbidden();
        $this->actingAs($this->user('staff'))->post(route('books.import.store'), ['file' => $this->csv("accession_number,title\n1,A\n")])->assertForbidden();

        $this->actingAs($this->user('librarian'))->get(route('books.import'))->assertOk()->assertSee('Import Books from Excel');
        $this->actingAs($this->user('librarian'))
            ->post(route('books.import.store'), ['file' => UploadedFile::fake()->createWithContent('old.xls', 'x')])
            ->assertSessionHasErrors('file');
    }

    public function test_the_catalog_has_an_import_button_for_librarians_only(): void
    {
        $this->actingAs($this->user('librarian'))->get(route('books.index'))->assertOk()->assertSee('Import from Excel');
        $this->actingAs($this->user('staff'))->get(route('books.index'))->assertOk()->assertDontSee('Import from Excel');
    }

    public function test_many_rows_can_be_imported_at_once(): void
    {
        $csv = "accession_number,title\n";
        for ($i = 1; $i <= 450; $i++) {
            $csv .= "BULK-{$i},Bulk Book {$i}\n";
        }

        $this->actingAs($this->user('librarian'))
            ->post(route('books.import.store'), ['file' => $this->csv($csv)])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 450);

        $this->assertSame(450, Book::count());
    }
}
