<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchShift extends Model
{
    use HasFactory;

    public const DAY_WEEKDAY  = 'weekday';
    public const DAY_SATURDAY = 'saturday';
    public const DAY_ALL      = 'all';

    protected $fillable = [
        'branch_id',
        'shift_name',
        'day_type',
        'start_time',
        'end_time',
        'cutoff_time',
        'pre_shift_minutes',
        'normalized_shift',
        'crosses_midnight',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'crosses_midnight' => 'boolean',
        'pre_shift_minutes' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Parse HH:MM into total minutes of the day (0..1439).
     */
    public static function timeToMinutes(string $time): int
    {
        [$h, $m] = array_pad(explode(':', trim($time)), 2, '0');
        return ((int)$h) * 60 + ((int)$m);
    }

    public function getStartMinutesAttribute(): int
    {
        return self::timeToMinutes($this->start_time);
    }

    public function getEndMinutesAttribute(): int
    {
        return self::timeToMinutes($this->end_time);
    }

    public function getCutoffMinutesAttribute(): int
    {
        return self::timeToMinutes($this->cutoff_time ?: $this->end_time);
    }

    public function getPreShiftStartMinutesAttribute(): int
    {
        $minutes = $this->start_minutes - ($this->pre_shift_minutes ?: 0);
        if ($minutes < 0) {
            $minutes += 1440;
        }
        return $minutes;
    }

    public function getEffectiveShiftNameAttribute(): string
    {
        return $this->normalized_shift ?: $this->shift_name;
    }

    /**
     * Checks if a given time of day (in minutes 0..1439) falls inside the base shift window.
     */
    public function matchesBaseWindow(int $minuteOfDay): bool
    {
        $start = $this->start_minutes;
        $end = $this->end_minutes;

        if ($this->crosses_midnight || $start > $end) {
            // e.g. 22:00 (1320) to 06:00 (360)
            return $minuteOfDay >= $start || $minuteOfDay < $end;
        }

        return $minuteOfDay >= $start && $minuteOfDay < $end;
    }

    /**
     * Checks if a given time of day (in minutes 0..1439) falls inside the pre-shift tolerance window.
     */
    public function matchesPreShiftWindow(int $minuteOfDay): bool
    {
        $preStart = $this->pre_shift_start_minutes;
        $shiftStart = $this->start_minutes;

        if ($preStart > $shiftStart) {
            // Window crosses midnight, e.g. Shift starts at 01:00, pre-shift is 23:00
            return $minuteOfDay >= $preStart || $minuteOfDay < $shiftStart;
        }

        return $minuteOfDay >= $preStart && $minuteOfDay < $shiftStart;
    }

    /**
     * Checks if a given analysis created_at falls before the cut-off time for this shift.
     */
    public function isWithinCutoff(int $minuteOfDay): bool
    {
        $start = $this->start_minutes;
        $cutoff = $this->cutoff_minutes;

        if ($this->crosses_midnight || $start > $cutoff) {
            return $minuteOfDay >= $start || $minuteOfDay <= $cutoff;
        }

        return $minuteOfDay >= $start && $minuteOfDay <= $cutoff;
    }
}

