<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentMatchResult extends Model
{
    protected $table = 'replenishment_match_results';

    protected $fillable = [
        'batch_id',
        'report_date',
        'report_shift',
        'category',
        'record_type',
        'item_no',
        'item_description',
        'from_location',
        'to_location',
        'task_key',
        'group_key',
        'qty_report',
        'qty_analysis',
        'qty_check',
        'status',
        'counts_as_task',
        'task_done',
        'tag_validation',
        'storage',
        'source_expired',
    ];

    protected $casts = [
        'qty_report'    => 'float',
        'qty_analysis'  => 'float',
        'counts_as_task'=> 'boolean',
        'task_done'     => 'boolean',
    ];

    /** Scope: only rows that count as a task (MATCH or PENDING) */
    public function scopeCountsAsTask($query): mixed
    {
        return $query->where('counts_as_task', true);
    }

    /** Scope: only completed/matched tasks */
    public function scopeTaskDone($query): mixed
    {
        return $query->where('task_done', true);
    }

    /** Scope: filter by category */
    public function scopeCategory($query, string $category): mixed
    {
        return $query->where('category', $category);
    }
}
