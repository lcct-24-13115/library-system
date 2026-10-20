<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: remarks history. Every new remark on a loan becomes one dated note with the
     * user's name, so nothing is overwritten. The old loans.remarks column is NOT changed or
     * migrated; it stays as the read-only "earlier remark".
     */
    public function up(): void
    {
        Schema::create('loan_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamps();

            $table->index(['loan_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_notes');
    }
};
