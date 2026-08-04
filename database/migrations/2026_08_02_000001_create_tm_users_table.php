<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing users table if exists
        Schema::dropIfExists('users');
        Schema::dropIfExists('tm_users');

        Schema::create('tm_users', function (Blueprint $table) {
            $table->bigIncrements('i_id');
            $table->string('v_userid', 100)->unique();
            $table->string('v_username', 255);
            $table->string('v_email', 255)->nullable();
            $table->string('v_password', 255)->nullable();
            $table->boolean('b_is_active')->default(true);
            $table->boolean('b_use_other')->default(false);

            // Kepegawaian / Unit
            $table->string('v_klogad', 15)->nullable();
            $table->string('v_kolok', 15)->nullable();
            $table->string('v_kojab', 10)->nullable();
            $table->string('v_koper', 10)->nullable();
            $table->string('v_kopang', 10)->nullable();
            $table->string('v_eselon', 4)->nullable();
            $table->string('v_spmu', 10)->nullable();
            $table->string('v_kd', 4)->nullable();

            $table->string('v_remember_token', 100)->nullable();
            $table->timestamp('dt_email_verified_at')->nullable();
            $table->timestamp('dt_last_updated_password')->nullable();

            // Audit trail
            $table->string('v_created_by', 100)->nullable();
            $table->timestamp('dt_created_at')->nullable();
            $table->string('v_updated_by', 100)->nullable();
            $table->timestamp('dt_updated_at')->nullable();
            $table->string('v_deleted_by', 100)->nullable();
            $table->timestamp('dt_deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('tm_users');
    }
};
