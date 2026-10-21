<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ID photo shown on the attendance kiosk so the student's identity can be
     * confirmed before "Record entry". Stored on the public disk.
     */
    public function up(): void
    {
        Schema::table('patrons', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('contact_no');
        });
    }

    public function down(): void
    {
        Schema::table('patrons', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
