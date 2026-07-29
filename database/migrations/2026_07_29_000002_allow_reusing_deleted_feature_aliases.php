<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_features', function (Blueprint $table) {
            $table->dropUnique('tm_features_v_alias_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX tm_features_active_alias_unique
             ON tm_features (v_alias)
             WHERE dt_deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tm_features_active_alias_unique');

        Schema::table('tm_features', function (Blueprint $table) {
            $table->unique('v_alias');
        });
    }
};
