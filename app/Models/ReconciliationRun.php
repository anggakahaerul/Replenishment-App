<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_import_id',
        'analysis_import_id',
        'status',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function reportImport(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'report_import_id');
    }

    public function analysisImport(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'analysis_import_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ReconciliationResult::class, 'run_id')->orderBy('row_order');
    }
}
