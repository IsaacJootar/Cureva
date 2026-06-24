<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
  protected $fillable = [
    'name',
    'is_current',
  ];

  protected $casts = [
    'is_current' => 'boolean',
  ];

  public function scopeCurrent($query)
  {
    return $query->where('is_current', true);
  }

  /**
   * Get all LGAs in this state
   */
  public function lgas(): HasMany
  {
    return $this->hasMany(Lga::class);
  }

  /**
   * Get all facilities in this state
   */
  public function facilities(): HasMany
  {
    return $this->hasMany(Facility::class);
  }
}
