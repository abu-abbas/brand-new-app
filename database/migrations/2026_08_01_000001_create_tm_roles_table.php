<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_roles', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('i_id');

            // Core fields
            $table->string('v_code', 100);
            $table->string('v_name', 255);
            $table->boolean('b_need_region')->default(false);
            $table->boolean('b_need_unit')->default(false);
            $table->string('v_active_periode', 255)->nullable();
            $table->boolean('b_locked')->default(false);

            // Audit trail
            $table->string('v_created_by', 100)->nullable();
            $table->timestamp('dt_created_at')->nullable();
            $table->string('v_updated_by', 100)->nullable();
            $table->timestamp('dt_updated_at')->nullable();

            // Soft delete
            $table->string('v_deleted_by', 100)->nullable();
            $table->timestamp('dt_deleted_at')->nullable();
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['oracle', 'oci8', 'pdo_oracle'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX tm_roles_active_code_unique
                 ON tm_roles (CASE WHEN dt_deleted_at IS NULL THEN v_code END)'
            );
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX tm_roles_active_code_unique
                 ON tm_roles (v_code, (CASE WHEN dt_deleted_at IS NULL THEN 1 ELSE NULL END))'
            );
        } else {
            DB::statement(
                'CREATE UNIQUE INDEX tm_roles_active_code_unique
                 ON tm_roles (v_code)
                 WHERE dt_deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['oracle', 'oci8', 'pdo_oracle'], true)) {
            DB::statement('DROP INDEX tm_roles_active_code_unique');
        } else {
            DB::statement('DROP INDEX IF EXISTS tm_roles_active_code_unique');
        }

        Schema::dropIfExists('tm_roles');
    }
};
