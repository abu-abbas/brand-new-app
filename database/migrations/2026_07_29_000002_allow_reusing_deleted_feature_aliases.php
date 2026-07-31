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

        $driver = DB::getDriverName();

        if (in_array($driver, ['oracle', 'oci8', 'pdo_oracle'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX tm_features_active_alias_unique
                 ON tm_features (CASE WHEN dt_deleted_at IS NULL THEN v_alias END)'
            );
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX tm_features_active_alias_unique
                 ON tm_features (v_alias, (CASE WHEN dt_deleted_at IS NULL THEN 1 ELSE NULL END))'
            );
        } else {
            DB::statement(
                'CREATE UNIQUE INDEX tm_features_active_alias_unique
                 ON tm_features (v_alias)
                 WHERE dt_deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['oracle', 'oci8', 'pdo_oracle'], true)) {
            DB::statement('DROP INDEX tm_features_active_alias_unique');
        } else {
            DB::statement('DROP INDEX IF EXISTS tm_features_active_alias_unique');
        }

        Schema::table('tm_features', function (Blueprint $table) {
            $table->unique('v_alias');
        });
    }
};
