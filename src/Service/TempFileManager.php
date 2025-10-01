<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\TempFileManagerInterface;
use App\Model\SimpleCakeDay;

/**
 * Service for managing temporary files during batch processing
 */
final class TempFileManager implements TempFileManagerInterface
{
    public function create(string $prefix): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        if (!$tempFile) {
            throw new \RuntimeException('Unable to create temporary file');
        }
        return $tempFile;
    }

    /**
     * Write batch of cake days to temporary file
     */
    public function writeBatch(string $tempFile, array $cakeDays): void
    {
        $handle = fopen($tempFile, 'a');
        if (!$handle) {
            throw new \RuntimeException('Unable to open temporary file for writing');
        }

        foreach ($cakeDays as $cakeDay) {
            $line = sprintf(
                "%s|%d|%d|%s\n",
                $cakeDay->timestamp,
                $cakeDay->smallCakes,
                $cakeDay->largeCakes,
                implode(',', $cakeDay->employeeNames)
            );
            fwrite($handle, $line);
        }

        fclose($handle);
    }

    /**
     * Consolidate results from temporary file WITHOUT any Carbon objects
     */
    public function consolidateResults(string $tempFile): array
    {
        $dateGroups = [];
        $handle = fopen($tempFile, 'r');

        if (!$handle) {
            throw new \RuntimeException('Unable to open temporary file for reading');
        }

        $lineCount = 0;

        // Read and group by timestamp (NO Carbon objects at all)
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode('|', $line, 4);
            if (count($parts) !== 4) continue;

            [$timestamp, $smallCakes, $largeCakes, $employeeNames] = $parts;

            if (!isset($dateGroups[$timestamp])) {
                $dateGroups[$timestamp] = [];
            }

            // Just collect names without ANY objects
            $names = !empty($employeeNames) ? explode(',', $employeeNames) : [];
            foreach ($names as $name) {
                $dateGroups[$timestamp][] = $name;
            }

            $lineCount++;

            // Very frequent cleanup
            if ($lineCount % 1000 === 0) {
                gc_collect_cycles();
            }
        }

        fclose($handle);

        // Create lightweight SimpleCakeDay objects (NO Carbon yet)
        $simpleCakeDays = [];
        $processedGroups = 0;

        foreach ($dateGroups as $timestamp => $employeeNames) {
            $employeeCount = count($employeeNames);

            $simpleCakeDays[] = new SimpleCakeDay(
                (int)$timestamp,
                $employeeCount >= 2 ? 0 : 1,  // small cakes
                $employeeCount >= 2 ? 1 : 0,  // large cakes
                $employeeNames
            );

            $processedGroups++;

            // Very aggressive cleanup
            if ($processedGroups % 50 === 0) {
                gc_collect_cycles();
            }
        }

        // Clear all intermediate data
        unset($dateGroups, $timestamp, $employeeNames);
        gc_collect_cycles();

        // Sort by timestamp (no Carbon needed)
        usort($simpleCakeDays, static fn($a, $b) => $a->timestamp <=> $b->timestamp);

        return $simpleCakeDays;
    }

    /**
     * Clean up temporary file
     */
    public function cleanup(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
