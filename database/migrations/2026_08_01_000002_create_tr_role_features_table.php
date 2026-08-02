<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_role_features', function (Blueprint $table) {
            $table->bigIncrements('i_id');
            $table->string('v_code', 100)->index();
            $table->string('v_alias', 100)->index();

            $table->unique(['v_code', 'v_alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_role_features');
    }
};
