<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'item_no',
        'item_description',
        'source_location_id',
        'destination_location_id',
        'source_location_code',
        'destination_location_code',
        'suggested_sources',
        'quantity',
        'unit',
        'available_qty',
        'in_location_qty',
        'safety_level',
        'max_level',
        'row_number',
    ];

    protected $casts = [
        'suggested_sources' => 'array',
        'quantity' => 'decimal:4',
        'available_qty' => 'decimal:4',
        'in_location_qty' => 'decimal:4',
        'safety_level' => 'decimal:4',
        'max_level' => 'decimal:4',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }
}
