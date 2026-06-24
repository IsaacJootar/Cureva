<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('immunization_registrations')) {
      return;
    }

    Schema::create('immunization_registrations', function (Blueprint $table) {
      $table->id();

      $table->foreignId('patient_id')->unique()->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      $table->date('registration_date');
      $table->string('follow_up_phone', 20)->nullable();
      $table->text('follow_up_address')->nullable();
      $table->text('notes')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();

      $table->index('facility_id');
      $table->index('registration_date');
    });
  }

  public function down(): void
  {
    if (!Schema::hasTable('immunization_registrations')) {
      return;
    }

    Schema::dropIfExists('immunization_registrations');
  }
};
