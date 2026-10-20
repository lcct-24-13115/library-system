<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student / Faculty portal accounts: a users row with role 'patron' points to its patron.
     * NULL for every staff account, so existing accounts are untouched.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('patron_id')->nullable()->unique()->after('library');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['patron_id']);
            $table->dropColumn('patron_id');
        });
    }
};
