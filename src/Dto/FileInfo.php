<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO for file information
 */
final readonly class FileInfo
{
    public function __construct(
        public string $filePath,
        public int $sizeBytes,
        public bool $exists,
        public bool $readable
    ) {}

    public function getSizeMB(): float
    {
        return round($this->sizeBytes / 1024 / 1024, 2);
    }

    public function isValid(): bool
    {
        return $this->exists && $this->readable;
    }

    public static function fromFilePath(string $filePath): self
    {
        $exists = file_exists($filePath);
        $readable = $exists && is_readable($filePath);
        $sizeBytes = $exists ? filesize($filePath) : 0;

        return new self(
            filePath: $filePath,
            sizeBytes: $sizeBytes,
            exists: $exists,
            readable: $readable
        );
    }
}
