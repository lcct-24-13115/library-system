<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily visitor log (one row per scan at the entrance / desk).
     * Purely a log: nothing here blocks borrowing or charges anyone.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('visited_at');
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->index('visited_at');
            $table->index(['patron_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
