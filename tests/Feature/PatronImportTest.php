<?php

namespace Tests\Feature;

use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use ZipArchive;
use Tests\TestCase;

/**
 * Bulk add patrons from an Excel (.xlsx) or CSV sheet. Additive only; nothing is stored or generated.
 */
class PatronImportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
    }

    private function csv(string $content, string $name = 'patrons.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_csv_import_adds_new_patrons_and_defaults_the_type_to_student(): void
    {
        $csv = "id_number,name,patron_type,email,contact_no\n24-00010,Ana Reyes,,ana@school.edu,0917\n24-00011,Prof Cruz,faculty,,\n";

        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => $this->csv($csv)])
            ->assertRedirect(route('patrons.import'));

        $this->assertSame('Student', Patron::where('id_number', '24-00010')->first()->patron_type);
        $this->assertSame('Faculty', Patron::where('id_number', '24-00011')->first()->patron_type);
        $this->assertSame('active', Patron::where('id_number', '24-00010')->first()->status);
        $this->assertSame('ana@school.edu', Patron::where('id_number', '24-00010')->first()->email);
    }

    public function test_existing_ids_duplicates_and_bad_rows_are_skipped_without_changing_anything(): void
    {
        Patron::create(['id_number' => '24-00001', 'name' => 'Original Name', 'patron_type' => 'Student', 'status' => 'active']);

        $csv = "ID Number,Name,Type,Email\n"
            . "24-00001,Changed Name,Student,\n"      // already exists
            . "24-00002,New One,Student,\n"
            . "24-00002,Same In File,Student,\n"      // duplicate inside the file
            . "24-00003,,Student,\n"                  // no name
            . "24-00004,Bad Type,Alien,\n"            // invalid type
            . "24-00005,Bad Mail,Student,not-an-email\n";

        $this->actingAs($this->user('admin'))
            ->post(route('patrons.import.store'), ['file' => $this->csv($csv)])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && count($r['skipped']) === 5 && $r['total'] === 6);

        $this->assertSame('Original Name', Patron::where('id_number', '24-00001')->value('name'));
        $this->assertSame(2, Patron::count());
        $this->assertSame('New One', Patron::where('id_number', '24-00002')->value('name'));
    }

    public function test_a_file_without_the_required_headings_is_refused(): void
    {
        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => $this->csv("foo,bar\n1,2\n")])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Patron::count());
    }

    public function test_an_xlsx_file_can_be_imported(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<si><t>ID Number</t></si><si><t>Name</t></si><si><t>Type</t></si><si><t>24-77777</t></si><si><t>Excel Student</t></si><si><t>Student</t></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            . '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row>'
            . '<row r="2"><c r="A2" t="s"><v>3</v></c><c r="B2" t="s"><v>4</v></c><c r="C2" t="s"><v>5</v></c></row>'
            . '<row r="3"><c r="A3"><v>2026001</v></c><c r="B3" t="inlineStr"><is><t>Numeric Id</t></is></c></row>'
            . '</sheetData></worksheet>');
        $zip->close();

        $file = UploadedFile::fake()->createWithContent('students.xlsx', file_get_contents($tmp));
        @unlink($tmp);

        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 2);

        $this->assertSame('Excel Student', Patron::where('id_number', '24-77777')->value('name'));
        $this->assertSame('Numeric Id', Patron::where('id_number', '2026001')->value('name'));
    }

    public function test_only_admin_and_librarian_can_import_and_other_formats_are_refused(): void
    {
        $this->actingAs($this->user('staff'))->get(route('patrons.import'))->assertForbidden();
        $this->actingAs($this->user('staff'))->post(route('patrons.import.store'), ['file' => $this->csv("id_number,name\n1,A\n")])->assertForbidden();

        $this->actingAs($this->user('librarian'))->get(route('patrons.import'))->assertOk()->assertSee('Import Patrons from Excel');
        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => UploadedFile::fake()->createWithContent('old.xls', 'x')])
            ->assertSessionHasErrors('file');
    }

    public function test_imported_students_can_sign_in_with_their_id_number(): void
    {
        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => $this->csv("id_number,name\n24-31000,Auto Student\n")]);
        auth()->logout();

        $this->post(route('login'), ['email' => '24-31000', 'password' => '24-31000'])->assertRedirect(route('portal.index'));
    }

    public function test_the_school_sheet_headings_studnum_and_studname_are_understood_and_extra_columns_ignored(): void
    {
        $csv = "studnum,studname,degree,major,yrlevel\n25-13573,\"DEL MUNDO, REIGN DANJEN M.\",BSA,NONE,1\n25-13457,\"SIMAN, JHODALYN A.\",BSA,NONE,1\n";

        $this->actingAs($this->user('librarian'))
            ->post(route('patrons.import.store'), ['file' => $this->csv($csv)])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 2 && count($r['skipped']) === 0);

        $this->assertSame('DEL MUNDO, REIGN DANJEN M.', Patron::where('id_number', '25-13573')->value('name'));
        $this->assertSame('Student', Patron::where('id_number', '25-13573')->value('patron_type'));
    }
}
