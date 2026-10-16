<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_id',
        'replenishment_detail_id',
        'analysis_detail_id',
        'sku',
        'nama_produk',
        'from_location_report',
        'from_location_report_type',
        'from_location_analysis',
        'from_location_analysis_type',
        'to_location',
        'to_location_type',
        'qty_replenish',
        'qty_replenish_unit',
        'qty_analysis',
        'qty_analysis_unit',
        'difference',
        'status',
        'notes',
        'row_order',
    ];

    protected $casts = [
        'qty_replenish' => 'decimal:4',
        'qty_analysis' => 'decimal:4',
        'difference' => 'decimal:4',
        'row_order' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class, 'run_id');
    }

    public function replenishmentDetail(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentDetail::class, 'replenishment_detail_id');
    }

    public function analysisDetail(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentAnalysisDetail::class, 'analysis_detail_id');
    }
}
