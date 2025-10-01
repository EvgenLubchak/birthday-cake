<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FileInfo;

/**
 * Service for file operations using FileInfo DTO
 */
final class FileService
{
    /**
     * Get file information as DTO
     */
    public function getFileInfo(string $filePath): FileInfo
    {
        return FileInfo::fromFilePath($filePath);
    }

    /**
     * Validate file for processing
     * 
     * @throws \InvalidArgumentException
     */
    public function validateInputFile(string $filePath): FileInfo
    {
        $fileInfo = $this->getFileInfo($filePath);

        if (!$fileInfo->exists) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!$fileInfo->readable) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        if ($fileInfo->sizeBytes === 0) {
            throw new \InvalidArgumentException("File is empty: {$filePath}");
        }

        return $fileInfo;
    }

    /**
     * Ensure output directory exists
     */
    public function ensureOutputDirectory(string $outputPath): void
    {
        $directory = dirname($outputPath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \RuntimeException("Cannot create output directory: {$directory}");
        }

        if (!is_writable($directory)) {
            throw new \RuntimeException("Output directory is not writable: {$directory}");
        }
    }
}
