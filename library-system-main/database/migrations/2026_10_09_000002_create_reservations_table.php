<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Book reservations (holds queue).
     *
     * status: pending | fulfilled | cancelled  (changed manually by the librarian)
     *
     * NOTE: intentionally NO fine / penalty / expiry-charge columns. A reservation
     * does not hold a physical copy and never blocks issuing or returning.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('patron_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->dateTime('reserved_at');
            $table->dateTime('fulfilled_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'reserved_at']);
            $table->index(['book_id', 'status']);
            $table->index(['patron_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
