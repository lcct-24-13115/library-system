<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The school has three libraries (College, Grade School, High School), like the legacy system.
 * - Accounts assigned to the old "Junior High School Library" or "Senior High School Library"
 *   are moved to "High School Library".
 * - books.library gets an index (the public catalog filters and counts by it).
 * No book data is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('library', ['Junior High School Library', 'Senior High School Library'])
            ->update(['library' => 'High School Library']);

        Schema::table('books', function (Blueprint $table) {
            $table->index('library');
        });
    }

    public function down(): void
    {
        // The old Junior / Senior High assignment cannot be restored (they were merged), only the index goes.
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['library']);
        });
    }
};
