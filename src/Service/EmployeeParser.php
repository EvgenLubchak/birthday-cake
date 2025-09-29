<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Employee;

/**
 * Service for parsing employee data from files
 */
final class EmployeeParser
{
    /**
     * Parse employees from a file
     * 
     * @return Employee[]
     * @throws \InvalidArgumentException
     */
    public function parseFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \InvalidArgumentException("Could not read file: {$filePath}");
        }

        return $this->parseFromString($content);
    }

    /**
     * Parse employees from string content
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
