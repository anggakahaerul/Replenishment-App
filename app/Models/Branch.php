<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'timezone',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(BranchShift::class)
            ->orderByRaw("CASE day_type WHEN 'weekday' THEN 1 WHEN 'saturday' THEN 2 ELSE 3 END")
            ->orderBy('start_time');
    }

    public function activeShifts(): HasMany
    {
        return $this->hasMany(BranchShift::class)
            ->where('is_active', true)
            ->orderByRaw("CASE day_type WHEN 'weekday' THEN 1 WHEN 'saturday' THEN 2 ELSE 3 END")
            ->orderBy('start_time');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

