<?php

declare(strict_types=1);

namespace App\Interface;

use App\Model\Employee;

interface EmployeeParserInterface
{
    /**
     * Parse employees from large files using streaming
     * 
     * @return \Generator<Employee>
     * @throws \InvalidArgumentException
     */
    public function parseFromFileStream(string $filePath): \Generator;

    /**
     * Parse employees in batches for memory efficiency
     * 
     * @return \Generator<Employee[]>
     * @throws \InvalidArgumentException
     */
    public function parseInBatches(string $filePath, int $batchSize = 1000): \Generator;
}
