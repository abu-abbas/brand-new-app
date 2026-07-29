<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_features', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('i_id');

            // Core fields
            $table->string('v_name', 100);
            $table->string('v_alias', 150)->unique();
            $table->string('e_type', 50);

            // Hierarchy & display
            $table->string('v_parent', 150)->nullable(); // ref ke v_alias parent
            $table->string('v_desc', 250)->nullable();
            $table->string('v_route', 250)->nullable();
            $table->string('v_icon', 50)->nullable();
            $table->smallInteger('si_order')->default(1);
            $table->boolean('b_show_on_sidebar')->default(false);

            // Audit trail
            $table->string('v_created_by', 50)->nullable();
            $table->timestamp('dt_created_at')->nullable();
            $table->string('v_updated_by', 50)->nullable();
            $table->timestamp('dt_updated_at')->nullable();

            // Soft delete
            $table->string('v_deleted_by', 50)->nullable();
            $table->timestamp('dt_deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_features');
    }
};
