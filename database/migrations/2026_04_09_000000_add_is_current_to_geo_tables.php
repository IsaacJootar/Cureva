<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('states', function (Blueprint $table) {
      $table->boolean('is_current')->default(true)->after('name');
    });

    Schema::table('lgas', function (Blueprint $table) {
      $table->boolean('is_current')->default(true)->after('name');
    });

    Schema::table('wards', function (Blueprint $table) {
      $table->boolean('is_current')->default(true)->after('name');
    });

    Schema::table('states', function (Blueprint $table) {
      $table->dropUnique('states_name_unique');
      $table->unique(['name', 'is_current'], 'states_name_is_current_unique');
    });

    Schema::table('lgas', function (Blueprint $table) {
      $table->dropUnique('lgas_state_id_name_unique');
      $table->unique(['state_id', 'name', 'is_current'], 'lgas_state_id_name_is_current_unique');
    });

    Schema::table('wards', function (Blueprint $table) {
      $table->dropUnique('wards_lga_id_name_unique');
      $table->unique(['lga_id', 'name', 'is_current'], 'wards_lga_id_name_is_current_unique');
    });
  }

  public function down(): void
  {
    Schema::table('wards', function (Blueprint $table) {
      $table->dropUnique('wards_lga_id_name_is_current_unique');
      $table->dropColumn('is_current');
      $table->unique(['lga_id', 'name'], 'wards_lga_id_name_unique');
    });

    Schema::table('lgas', function (Blueprint $table) {
      $table->dropUnique('lgas_state_id_name_is_current_unique');
      $table->dropColumn('is_current');
      $table->unique(['state_id', 'name'], 'lgas_state_id_name_unique');
    });

    Schema::table('states', function (Blueprint $table) {
      $table->dropUnique('states_name_is_current_unique');
      $table->dropColumn('is_current');
      $table->unique('name', 'states_name_unique');
    });
  }
};
