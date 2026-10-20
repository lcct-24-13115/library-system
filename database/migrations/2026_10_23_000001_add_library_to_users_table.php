<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which library an account works in (see App\Models\User::LIBRARIES).
     * NULL = "All libraries", so every existing account keeps working as before.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('library')->nullable()->after('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('library');
        });
    }
};
