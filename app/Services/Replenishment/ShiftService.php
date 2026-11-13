<?php

namespace App\Services\Replenishment;

use App\Models\Branch;
use App\Models\BranchShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shift detection, cut-off evaluation, and normalization logic per Branch.
 */
class ShiftService
{
    /**
     * Cache active shifts by branch ID for high-volume row iteration.
     *
     * @var array<int, Collection<int, BranchShift>>
     */
    private array $branchShiftsCache = [];

    /**
     * Get active shifts for a branch (with local caching).
     *
     * @return Collection<int, BranchShift>
     */
    public function getBranchShifts(?int $branchId = null): Collection
    {
        $key = $branchId ?: 0;
        if (isset($this->branchShiftsCache[$key])) {
            return $this->branchShiftsCache[$key];
        }

        $branch = null;
        if ($branchId) {
            $branch = Branch::with('activeShifts')->find($branchId);
        }

        if (!$branch) {
            // Default to first active branch or DC-NORTH
            $branch = Branch::with('activeShifts')->where('is_active', true)->first();
        }

        $shifts = $branch ? $branch->activeShifts : collect();
        $this->branchShiftsCache[$key] = $shifts;

        return $shifts;
    }

    /**
     * Filter branch shifts matching the date's day of week (Saturday vs Weekday).
     * If it is Saturday and the branch has specific 'saturday' shifts configured, use those.
     * Otherwise fall back to 'weekday' / 'all' shifts.
     *
     * @return Collection<int, BranchShift>
     */
    public function getBranchShiftsForDate(Carbon $date, ?int $branchId = null): Collection
    {
        $allShifts = $this->getBranchShifts($branchId);
        if ($allShifts->isEmpty()) {
            return $allShifts;
        }

        $isSaturday = $date->isSaturday();

        if ($isSaturday) {
            $satShifts = $allShifts->filter(fn(BranchShift $s) => in_array($s->day_type, ['saturday', 'all'], true))->values();
            if ($satShifts->isNotEmpty()) {
                return $satShifts;
            }
        }

        // Weekday (Senin - Jumat) or fallback when no Saturday shifts are defined
        $weekdayShifts = $allShifts->filter(fn(BranchShift $s) => in_array($s->day_type ?: 'weekday', ['weekday', 'all'], true))->values();

        return $weekdayShifts->isNotEmpty() ? $weekdayShifts : $allShifts;
    }

    /**
     * Detect shift for a Report based on printed_at timestamp and branch schedule.
     * Priority order:
     * 1. 00:00 - 05:59 -> forced to SHIFT 1 (morning shift preparation, never pulled into previous day's Shift 3).
     * 2. Pre-shift tolerance window [start - pre_shift_minutes, start) -> assigned to that shift (normalized).
     * 3. Normal base working window -> assigned to that shift (normalized).
     */
    public function detectReportShift(Carbon $printedAt, ?int $branchId = null): string
    {
        $localTime = $printedAt->copy()->setTimezone('Asia/Jakarta');
        $totalMinutes = $localTime->hour * 60 + $localTime->minute;

        // 1. 00:00 - 05:59 -> force SHIFT 1 (persiapan shift pagi)
        if ($totalMinutes < 360) {
            return 'SHIFT 1';
        }

        $shifts = $this->getBranchShiftsForDate($localTime, $branchId);

        if ($shifts->isEmpty()) {
            return $this->fallbackDetectReportShift($localTime);
        }

        // 2. Check pre-shift tolerance windows first (e.g. printed 60m before shift starts)
        foreach ($shifts as $shift) {
            if ($shift->matchesPreShiftWindow($totalMinutes)) {
                return $shift->effective_shift_name;
            }
        }

        // 3. Check base shift windows
        foreach ($shifts as $shift) {
            if ($shift->matchesBaseWindow($totalMinutes)) {
                return $shift->effective_shift_name;
            }
        }

        return $shifts->first()->effective_shift_name;
    }

    /**
     * Detect shift for an Analysis row based on Created timestamp and branch schedule.
     * Uses pure base working hours only (no 60-minute pre-shift tolerance, no 00:00-05:59 override),
     * then normalizes SHIFT 3 -> SHIFT 2.
     */
    public function detectAnalysisShift(Carbon $createdAt, ?int $branchId = null): string
    {
        $localTime = $createdAt->copy()->setTimezone('Asia/Jakarta');
        $shifts = $this->getBranchShiftsForDate($localTime, $branchId);

        if ($shifts->isEmpty()) {
            return $this->fallbackDetectAnalysisShift($localTime);
        }

        $totalMinutes = $localTime->hour * 60 + $localTime->minute;

        foreach ($shifts as $shift) {
            if ($shift->matchesBaseWindow($totalMinutes)) {
                return $shift->effective_shift_name;
            }
        }

        return $shifts->first()->effective_shift_name;
    }

    /**
     * Evaluates if an analysis row matches the target shift and is within cut-off time.
     */
    public function isAnalysisMatchingShift(Carbon $createdAt, string $reportShift, ?int $branchId = null): bool
    {
        $localTime = $createdAt->copy()->setTimezone('Asia/Jakarta');
        $shifts = $this->getBranchShiftsForDate($localTime, $branchId);

        if ($shifts->isEmpty()) {
            return $this->detectAnalysisShift($localTime, $branchId) === $reportShift;
        }

        $totalMinutes = $localTime->hour * 60 + $localTime->minute;

        foreach ($shifts as $shift) {
            if ($shift->effective_shift_name === $reportShift && $shift->matchesBaseWindow($totalMinutes)) {
                // Must be within cut-off time
                return $shift->isWithinCutoff($totalMinutes);
            }
        }

        return false;
    }

    /**
     * Normalize shift name (legacy support).
     */
    public function normalizeShift(string $shift): string
    {
        return $shift === 'SHIFT 3' ? 'SHIFT 2' : $shift;
    }

    /**
     * Legacy fallback logic when no database shifts exist.
     */
    private function fallbackDetectReportShift(Carbon $printedAt): string
    {
        $totalMinutes = $printedAt->hour * 60 + $printedAt->minute;
        if ($totalMinutes < 360) {
            return 'SHIFT 1';
        }

        if ($printedAt->isSaturday()) {
            // Saturday pre-shift 60m: Shift 2 starts 11:00 (pre 10:00-10:59), Shift 3 starts 16:00 (pre 15:00-15:59)
            if ($totalMinutes >= 600 && $totalMinutes < 660) return 'SHIFT 2';
            if ($totalMinutes >= 900 && $totalMinutes < 960) return 'SHIFT 2'; // SHIFT 3 normalized
            return $this->normalizeShift($this->fallbackBaseShift($totalMinutes, true));
        }

        // Weekday & Sunday pre-shift 60m: Shift 2 starts 14:00 (pre 13:00-13:59), Shift 3 starts 22:00 (pre 21:00-21:59)
        if ($totalMinutes >= 780 && $totalMinutes < 840) return 'SHIFT 2';
        if ($totalMinutes >= 1260 && $totalMinutes < 1320) return 'SHIFT 2'; // SHIFT 3 normalized
        return $this->normalizeShift($this->fallbackBaseShift($totalMinutes, false));
    }

    private function fallbackDetectAnalysisShift(Carbon $createdAt): string
    {
        $totalMinutes = $createdAt->hour * 60 + $createdAt->minute;
        return $this->normalizeShift($this->fallbackBaseShift($totalMinutes, $createdAt->isSaturday()));
    }

    private function fallbackBaseShift(int $totalMinutes, bool $isSaturday = false): string
    {
        if ($isSaturday) {
            if ($totalMinutes >= 360 && $totalMinutes < 660) return 'SHIFT 1';  // 06:00 - 10:59
            if ($totalMinutes >= 660 && $totalMinutes < 960) return 'SHIFT 2';  // 11:00 - 15:59
            return 'SHIFT 3';                                                   // 16:00 - 05:59
        }

        if ($totalMinutes >= 360 && $totalMinutes < 840) return 'SHIFT 1';      // 06:00 - 13:59
        if ($totalMinutes >= 840 && $totalMinutes < 1320) return 'SHIFT 2';     // 14:00 - 21:59
        return 'SHIFT 3';                                                       // 22:00 - 05:59
    }
}
