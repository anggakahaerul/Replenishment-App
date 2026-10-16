<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'file_name',
        'report_date',
        'file_hash',
        'status',
        'row_count',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function replenishmentDetails(): HasMany
    {
        return $this->hasMany(ReplenishmentDetail::class, 'import_id');
    }

    public function analysisDetails(): HasMany
    {
        return $this->hasMany(ReplenishmentAnalysisDetail::class, 'import_id');
    }
}
