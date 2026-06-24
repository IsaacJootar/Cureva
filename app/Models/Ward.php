<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
  protected $fillable = [
    'lga_id',
    'name',
    'is_current',
  ];

  protected $casts = [
    'lga_id' => 'integer',
    'is_current' => 'boolean',
  ];

  public function scopeCurrent($query)
  {
    return $query->where('is_current', true);
  }

  /**
   * Get the LGA this ward belongs to
   */
  public function lga(): BelongsTo
  {
    return $this->belongsTo(Lga::class);
  }

  /**
   * Get all facilities in this ward
   */
  public function facilities(): HasMany
  {
    return $this->hasMany(Facility::class);
  }
}
