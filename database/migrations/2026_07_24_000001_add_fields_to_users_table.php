<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('username')->nullable()->unique()->after('name');
      $table->string('unit_name')->nullable()->after('email');
      $table->string('role')->default('Staff')->after('unit_name');
      $table->boolean('is_active')->default(true)->after('role');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['username', 'unit_name', 'role', 'is_active']);
    });
  }
};
