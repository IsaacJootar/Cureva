<?php

namespace App\Models\Registrations;

use App\Models\Facility;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmunizationRegistration extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'registration_date',
    'follow_up_phone',
    'follow_up_address',
    'notes',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'registration_date' => 'date',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
