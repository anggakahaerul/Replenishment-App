<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentAnalysisDetail extends Model
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
        'whole_qty',
        'whole_uom',
        'loose_qty',
        'loose_uom',
        'ratio',
        'storage',
        'tag_validation',
        'tran_date',
        'row_number',
    ];

    protected $casts = [
        'whole_qty' => 'decimal:4',
        'loose_qty' => 'decimal:4',
        'ratio' => 'decimal:4',
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
