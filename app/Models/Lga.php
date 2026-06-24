<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lga extends Model
{
  protected $fillable = [
    'state_id',
    'name',
    'is_current',
  ];

  protected $casts = [
    'state_id' => 'integer',
    'is_current' => 'boolean',
  ];

  public function scopeCurrent($query)
  {
    return $query->where('is_current', true);
  }

  /**
   * Get the state this LGA belongs to
   */
  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class);
  }

  /**
   * Get all wards in this LGA
   */
  public function wards(): HasMany
  {
    return $this->hasMany(Ward::class);
  }

  /**
   * Get all facilities in this LGA
   */
  public function facilities(): HasMany
  {
    return $this->hasMany(Facility::class);
  }
}
