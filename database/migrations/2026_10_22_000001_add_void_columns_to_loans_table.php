<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: void a loan that was issued by mistake. Additive and nullable.
     * The loan row is never deleted; these columns only record that, when, by whom and why.
     * Existing rows are NOT touched (all three stay NULL).
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable(); // users.id
            $table->string('void_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
        });
    }
};
