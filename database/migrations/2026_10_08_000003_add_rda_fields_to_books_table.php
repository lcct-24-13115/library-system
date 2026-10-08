<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the RDA (Resource Description and Access) description fields,
     * classification, location and acquisition data to the existing books table.
     *
     * Every column is nullable, so existing catalog records stay valid.
     * Existing columns (accession_number, title, author, isbn, category,
     * total_copies, available_copies) are NOT changed.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Title & responsibility
            $table->string('subtitle')->nullable()->after('title');
            $table->string('statement_of_responsibility')->nullable()->after('author');

            // Publication
            $table->string('edition_statement')->nullable();
            $table->string('place_of_publication')->nullable();
            $table->string('publisher')->nullable();
            $table->string('year_of_publication', 20)->nullable(); // RDA allows e.g. "c2011"
            $table->string('series_statement')->nullable();

            // RDA content / media / carrier
            $table->string('content_type')->nullable();
            $table->string('media_type')->nullable();
            $table->string('carrier_type')->nullable();

            // Physical description
            $table->string('extent')->nullable();      // e.g. "95 pages"
            $table->string('dimensions')->nullable();  // e.g. "31 cm"
            $table->string('language')->nullable();

            // Classification & subjects
            $table->string('call_number')->nullable();
            $table->text('subjects')->nullable();      // separate with semicolons
            $table->string('genre')->nullable();
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();

            // Location
            $table->string('library')->nullable();
            $table->string('location')->nullable();

            // Acquisition
            $table->date('date_acquired')->nullable();
            $table->string('dealer_donor')->nullable();
            $table->decimal('price', 10, 2)->nullable();

            $table->index('call_number');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['call_number']);
            $table->dropColumn([
                'subtitle', 'statement_of_responsibility', 'edition_statement',
                'place_of_publication', 'publisher', 'year_of_publication',
                'series_statement', 'content_type', 'media_type', 'carrier_type',
                'extent', 'dimensions', 'language', 'call_number', 'subjects',
                'genre', 'summary', 'notes', 'library', 'location',
                'date_acquired', 'dealer_donor', 'price',
            ]);
        });
    }
};
