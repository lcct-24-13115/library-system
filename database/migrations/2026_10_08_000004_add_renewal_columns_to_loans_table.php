<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedSmallInteger('renewal_count')->default(0);
            $table->date('original_due_date')->nullable(); // set on the first renewal
            $table->dateTime('last_renewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['renewal_count', 'original_due_date', 'last_renewed_at']);
        });
    }
};
