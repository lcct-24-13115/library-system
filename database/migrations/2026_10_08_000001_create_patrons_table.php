<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Patrons are the borrowers (students, faculty, staff, outside users).
     * The ID number is what gets typed or scanned at the circulation desk.
     */
    public function up(): void
    {
        Schema::create('patrons', function (Blueprint $table) {
            $table->id();
            $table->string('id_number')->unique();
            $table->string('name');
            $table->string('patron_type')->default('Student');
            $table->string('email')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('status')->default('active'); // active | inactive (set manually by the librarian)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrons');
    }
};
