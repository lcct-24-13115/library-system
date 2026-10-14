<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles, allowed user group and active flag for staff accounts.
     *
     * role      : admin | librarian | staff (new accounts default to staff)
     * user_type : one of the five client-approved groups (App\Models\User::USER_TYPES).
     *             Nullable at the DB level only so existing rows keep working;
     *             the Users forms require it.
     * is_active : deactivated accounts cannot log in (accounts are never hard-deleted).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('password');
            $table->string('user_type')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('user_type');
        });

        // Everyone who exists before this migration was created by the developer/admin.
        DB::table('users')->update([
            'role'      => 'admin',
            'user_type' => 'Non-Teaching Personnel',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'user_type', 'is_active']);
        });
    }
};
