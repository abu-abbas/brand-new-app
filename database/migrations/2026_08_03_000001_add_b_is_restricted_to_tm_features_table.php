<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tm_features', 'b_is_restricted')) {
            Schema::table('tm_features', function (Blueprint $table) {
                $table->boolean('b_is_restricted')->default(false)->after('b_show_on_sidebar');
            });
        }

        DB::table('tm_features')
            ->whereNull('b_is_restricted')
            ->update(['b_is_restricted' => false]);

        // Tandai fitur-fitur core/internal manajemen fitur sebagai b_is_restricted = true
        DB::table('tm_features')
            ->whereIn(
                'v_alias',
                [
                    'manajemen-fitur',
                    'tambah-fitur',
                    'ubah-fitur',
                    'hapus-fitur',
                    'impersonate-pengguna',
                    'example',
                    'blank-page',
                ]
            )
            ->update(['b_is_restricted' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tm_features', 'b_is_restricted')) {
            Schema::table('tm_features', function (Blueprint $table) {
                $table->dropColumn('b_is_restricted');
            });
        }
    }
};
