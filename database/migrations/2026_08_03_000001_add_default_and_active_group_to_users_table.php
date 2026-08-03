<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_users', function (Blueprint $table) {
            $table->string('v_default_group_id', 100)->nullable()->after('b_use_other');
        });
    }

    public function down(): void
    {
        Schema::table('tm_users', function (Blueprint $table) {
            $table->dropColumn('v_default_group_id');
        });
    }
};
