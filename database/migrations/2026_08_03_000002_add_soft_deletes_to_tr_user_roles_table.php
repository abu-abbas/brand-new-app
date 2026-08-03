<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tr_user_roles', function (Blueprint $table) {
            $table->string('v_deleted_by', 100)->nullable();
            $table->timestamp('dt_deleted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('tr_user_roles', function (Blueprint $table) {
            $table->dropColumn(['v_deleted_by', 'dt_deleted_at']);
        });
    }
};
