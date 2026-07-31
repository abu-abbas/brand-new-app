<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_role_features', function (Blueprint $table) {
            $table->foreignId('i_role_id')
                ->constrained('tm_roles', 'i_id')
                ->cascadeOnDelete();

            $table->foreignId('i_feature_id')
                ->constrained('tm_features', 'i_id')
                ->cascadeOnDelete();

            $table->primary(['i_role_id', 'i_feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_role_features');
    }
};
