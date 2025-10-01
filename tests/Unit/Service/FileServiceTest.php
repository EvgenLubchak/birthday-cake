<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Dto\FileInfo;
use App\Service\FileService;
use PHPUnit\Framework\TestCase;

final class FileServiceTest extends TestCase
{
    private FileService $fileService;
    private string $tempFile;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->fileService = new FileService();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_file_');
        $this->tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();

        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testGetFileInfoReturnsFileInfoDto(): void
    {
        // Act
        $fileInfo = $this->fileService->getFileInfo($this->tempFile);

        // Assert
        $this->assertInstanceOf(FileInfo::class, $fileInfo);
        $this->assertEquals($this->tempFile, $fileInfo->filePath);
        $this->assertTrue($fileInfo->exists);
        $this->assertTrue($fileInfo->readable);
        $this->assertGreaterThan(0, $fileInfo->sizeBytes);
    }

    public function testValidateInputFileSucceedsForValidFile(): void
    {
        // Act
        $fileInfo = $this->fileService->validateInputFile($this->tempFile);

        // Assert
        $this->assertInstanceOf(FileInfo::class, $fileInfo);
        $this->assertTrue($fileInfo->isValid());
    }

    public function testValidateInputFileThrowsExceptionForNonExistentFile(): void
    {
        // Arrange
        $nonExistentFile = '/path/to/non/existent/file.txt';

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File does not exist: {$nonExistentFile}");

        $this->fileService->validateInputFile($nonExistentFile);
    }

    public function testValidateInputFileThrowsExceptionForEmptyFile(): void
    {
        // Arrange
        $emptyFile = tempnam(sys_get_temp_dir(), 'empty_');
        file_put_contents($emptyFile, ''); // Create empty file

        try {
            // Act & Assert
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("File is empty: {$emptyFile}");

            $this->fileService->validateInputFile($emptyFile);
        } finally {
            unlink($emptyFile);
        }
    }

    public function testEnsureOutputDirectoryCreatesDirectory(): void
    {
        // Arrange
        $outputPath = $this->tempDir . '/subdir/output.csv';

        // Act
        $this->fileService->ensureOutputDirectory($outputPath);

        // Assert
        $this->assertDirectoryExists(dirname($outputPath));
    }

    public function testEnsureOutputDirectoryWorksForExistingDirectory(): void
    {
        // Arrange
        mkdir($this->tempDir, 0755, true);
        $outputPath = $this->tempDir . '/output.csv';

        // Act & Assert - Should not throw exception
        $this->fileService->ensureOutputDirectory($outputPath);

        $this->assertDirectoryExists($this->tempDir);
    }
}
