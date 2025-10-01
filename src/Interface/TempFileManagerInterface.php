<?php

declare(strict_types=1);

namespace App\Interface;

interface TempFileManagerInterface
{
    /**
     * Create temporary file with given prefix
     */
    public function create(string $prefix): string;

    /**
     * Write batch of cake days to temporary file
     */
    public function writeBatch(string $tempFile, array $cakeDays): void;

    /**
     * Consolidate results from temporary file
     */
    public function consolidateResults(string $tempFile): array;

    /**
     * Clean up temporary file
     */
    public function cleanup(string $tempFile): void;
}
