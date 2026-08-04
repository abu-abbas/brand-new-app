<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateEmail = DB::table('tm_users')
            ->whereNotNull('v_email')
            ->selectRaw('LOWER(TRIM(v_email)) AS normalized_email, COUNT(*) AS total')
            ->groupByRaw('LOWER(TRIM(v_email))')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateEmail !== null) {
            throw new RuntimeException('Normalisasi email dibatalkan: terdapat email pengguna duplikat.');
        }

        DB::table('tm_users')
            ->whereNotNull('v_email')
            ->update(['v_email' => DB::raw('LOWER(TRIM(v_email))')]);

        $now = now();
        DB::table('tm_users')
            ->where('b_use_other', false)
            ->whereNotNull('v_password')
            ->whereNull('dt_last_updated_password')
            ->update(['dt_last_updated_password' => $now]);

        DB::table('tm_users')
            ->where('b_use_other', false)
            ->whereNotNull('v_email')
            ->whereNull('dt_email_verified_at')
            ->update(['dt_email_verified_at' => $now]);

        Schema::table('tm_users', function (Blueprint $table) {
            $table->unique('v_email', 'tm_users_v_email_unique');
        });

        DB::table('tm_features')->insertOrIgnore([
            'v_name' => 'Reset Password Pengguna',
            'v_alias' => 'reset-password-pengguna',
            'e_type' => 'crud',
            'v_parent' => 'manajemen-pengguna',
            'v_desc' => 'Permission kirim tautan reset password pengguna',
            'si_order' => 5340,
            'b_show_on_sidebar' => false,
            'b_is_restricted' => true,
            'v_created_by' => 'sysadmin',
            'dt_created_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('tm_features')
            ->where('v_alias', 'reset-password-pengguna')
            ->delete();

        Schema::table('tm_users', function (Blueprint $table) {
            $table->dropUnique('tm_users_v_email_unique');
        });
    }
};
