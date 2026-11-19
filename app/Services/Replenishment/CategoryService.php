<?php

namespace App\Services\Replenishment;

/**
 * Determines the storage category (DRY / FROZEN) for a replenishment task.
 *
 * Priority:
 *  1. Prefix of to_location  : D. → DRY,  F. → FROZEN
 *  2. Prefix of from_location : D. → DRY,  F. → FROZEN
 *  3. Storage column value    : DRY / FROZEN (case-insensitive)
 *  4. Fallback                : DRY
 */
class CategoryService
{
    /**
     * Detect storage category for a single replenishment task row.
     *
     * @param string|null $toLocation
     * @param string|null $fromLocation
     * @param string|null $storage       Value from the 'Storage' column in the analisa file
     * @return string  'DRY' or 'FROZEN'
     */
    public function detectCategory(
        ?string $toLocation,
        ?string $fromLocation,
        ?string $storage
    ): string {
        // Priority 1: Explicit Storage column value (most reliable when present)
        if ($storage !== null && $storage !== '') {
            $upper = strtoupper(trim($storage));
            if (str_contains($upper, 'FROZEN')) {
                return 'FROZEN';
            }
            if (str_contains($upper, 'DRY')) {
                return 'DRY';
            }
        }

        // Priority 2: to_location prefix
        if ($toLocation !== null && $toLocation !== '') {
            $result = $this->detectFromLocationPrefix($toLocation);
            if ($result !== null) {
                return $result;
            }
        }

        // Priority 3: from_location prefix
        if ($fromLocation !== null && $fromLocation !== '') {
            $result = $this->detectFromLocationPrefix($fromLocation);
            if ($result !== null) {
                return $result;
            }
        }

        // Priority 4: Default fallback
        return 'DRY';
    }

    /**
     * Detect category from a location code prefix.
     * Returns null if prefix does not match D. or F.
     */
    private function detectFromLocationPrefix(string $location): ?string
    {
        $upper = strtoupper(trim($location));

        if (str_starts_with($upper, 'D.')) {
            return 'DRY';
        }

        if (str_starts_with($upper, 'F.')) {
            return 'FROZEN';
        }

        return null;
    }
}
