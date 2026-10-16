<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_code',
        'location_name',
        'storage_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find or create location by normalized code.
     */
    public static function findOrCreateByCode(string $code, ?string $storageType = null): self
    {
        $normalized = trim($code);
        if ($normalized === '') {
            return static::firstOrCreate(
                ['location_code' => '-'],
                ['location_name' => 'Unknown Location', 'is_active' => true]
            );
        }

        return static::firstOrCreate(
            ['location_code' => $normalized],
            [
                'location_name' => $normalized,
                'storage_type' => $storageType,
                'is_active' => true,
            ]
        );
    }
}
