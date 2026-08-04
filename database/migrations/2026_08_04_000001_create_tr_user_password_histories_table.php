<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_user_password_histories', function (Blueprint $table) {
            $table->bigIncrements('i_id');
            $table->string('v_userid', 100)->index();
            $table->string('v_password_hash', 255);
            $table->timestamp('dt_created_at')->nullable();

            $table->foreign('v_userid')
                ->references('v_userid')
                ->on('tm_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_user_password_histories');
    }
};
