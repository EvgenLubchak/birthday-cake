<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Employee;

/**
 * Service for parsing employee data from files with streaming support
 */
final class EmployeeParser
{
    private const MAX_LINE_LENGTH = 1024;
    private const DEFAULT_BATCH_SIZE = 1000;


    /**
     * Parse employees from large files using streaming
     * 
     * @return \Generator<Employee>
     * @throws \InvalidArgumentException
     */
    public function parseFromFileStream(string $filePath): \Generator
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \InvalidArgumentException("Cannot open file: {$filePath}");
        }

        $lineNumber = 0;

        try {
            while (($line = fgets($handle, self::MAX_LINE_LENGTH)) !== false) {
                $lineNumber++;
                $line = trim($line);

                // Skip empty lines and comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                try {
                    yield Employee::fromCsvLine($line);
                } catch (\InvalidArgumentException $e) {
                    throw new \InvalidArgumentException(
                        "Error on line {$lineNumber}: " . $e->getMessage()
                    );
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse employees in batches for memory efficiency
     * 
     * @return \Generator<Employee[]>
     * @throws \InvalidArgumentException
     */
    public function parseInBatches(string $filePath, int $batchSize = self::DEFAULT_BATCH_SIZE): \Generator
    {
        $batch = [];
        $count = 0;

        foreach ($this->parseFromFileStream($filePath) as $employee) {
            $batch[] = $employee;
            $count++;

            if ($count >= $batchSize) {
                yield $batch;
                $batch = [];
                $count = 0;
            }
        }

        // Yield remaining employees
        if (!empty($batch)) {
            yield $batch;
        }
    }

    /**
     * Count total employees in file without loading all into memory
     * 
     * @throws \InvalidArgumentException
     */
    public function countEmployeesInFile(string $filePath): int
    {
        $count = 0;
        foreach ($this->parseFromFileStream($filePath) as $employee) {
            $count++;
        }
        return $count;
    }

    /**
     * Parse employees from string content (legacy method for testing)
     * 
     * @return Employee[]
     */
    public function parseFromString(string $content): array
    {
        $lines = array_filter(
            array_map('trim', explode("\n", $content)),
            fn(string $line) => !empty($line) && !str_starts_with($line, '#')
        );

        if (empty($lines)) {
            throw new \InvalidArgumentException("No valid employee data found");
        }

        $employees = [];
        foreach ($lines as $lineNumber => $line) {
            try {
                $employees[] = Employee::fromCsvLine($line);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException(
                    "Error on line " . ($lineNumber + 1) . ": " . $e->getMessage()
                );
            }
        }

        return $employees;
    }
}
