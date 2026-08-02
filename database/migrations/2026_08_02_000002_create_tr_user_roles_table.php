<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_user_roles', function (Blueprint $table) {
            $table->bigIncrements('i_id');
            $table->string('v_userid', 100)->index();
            $table->string('v_role_code', 100)->index();
            $table->string('v_wilayah', 50)->nullable();
            $table->string('v_unit', 50)->nullable();
            $table->string('v_pelaksana', 10)->nullable();
            $table->date('dt_valid_from')->nullable();
            $table->date('dt_valid_until')->nullable();

            // Audit trail
            $table->string('v_created_by', 100)->nullable();
            $table->timestamp('dt_created_at')->nullable();
            $table->string('v_updated_by', 100)->nullable();
            $table->timestamp('dt_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_user_roles');
    }
};
