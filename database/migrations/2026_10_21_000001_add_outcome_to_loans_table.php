<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3: how a closed loan ended - 'returned', 'lost' or 'damaged'.
     * Additive and nullable. Existing rows are NOT touched: NULL is read as 'returned'.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('outcome', 20)->nullable()->after('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('outcome');
        });
    }
};
