<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'import_type',
        'batch_id',
        'file_name',
        'file_path',
        'report_date',
        'file_hash',
        'status',
        'row_count',
        'imported_at',
        'report_shift',
        'site',
        'printed_by',
        'printed_at',
        'tran_date',
        'warehouse',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'printed_at'  => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function replenishmentDetails(): HasMany
    {
        return $this->hasMany(ReplenishmentDetail::class, 'import_id');
    }

    public function analysisDetails(): HasMany
    {
        return $this->hasMany(ReplenishmentAnalysisDetail::class, 'import_id');
    }
}
