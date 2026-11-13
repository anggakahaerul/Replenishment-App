<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentDailySummary extends Model
{
    protected $table = 'replenishment_daily_summaries';

    protected $fillable = [
        'branch_id',
        'report_date',
        'category',
        'shift_1_total',
        'shift_1_done',
        'shift_1_completion',
        'shift_2_total',
        'shift_2_done',
        'shift_2_completion',
        'total_tasks',
        'match_count',
        'pending_count',
        'match_percentage',
        'initiative_count',
        'put_away_count',
        'transfer_count',
        'duplicate_raw_count',
        'surplus_poc_count',
    ];

    protected $casts = [
        'shift_1_completion' => 'float',
        'shift_2_completion' => 'float',
        'match_percentage'   => 'float',
    ];
}
