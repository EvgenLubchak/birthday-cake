<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use App\Dto\FileInfo;
use PHPUnit\Framework\TestCase;

final class FileInfoTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_file_');
        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testConstructorAndProperties(): void
    {
        // Arrange & Act
        $fileInfo = new FileInfo(
            filePath: '/path/to/file.txt',
            sizeBytes: 1024,
            exists: true,
            readable: true
        );

        // Assert
        $this->assertEquals('/path/to/file.txt', $fileInfo->filePath);
        $this->assertEquals(1024, $fileInfo->sizeBytes);
        $this->assertTrue($fileInfo->exists);
        $this->assertTrue($fileInfo->readable);
    }

    public function testGetSizeMB(): void
    {
        // Arrange
        $fileInfo = new FileInfo('/path/to/file.txt', 2097152, true, true); // 2MB

        // Act
        $sizeMB = $fileInfo->getSizeMB();

        // Assert
        $this->assertEquals(2.0, $sizeMB);
    }

    public function testIsValidReturnsTrueWhenExistsAndReadable(): void
    {
        // Arrange
        $fileInfo = new FileInfo('/path/to/file.txt', 1024, true, true);

        // Act
        $isValid = $fileInfo->isValid();

        // Assert
        $this->assertTrue($isValid);
    }

    public function testIsValidReturnsFalseWhenNotExists(): void
    {
        // Arrange
        $fileInfo = new FileInfo('/path/to/file.txt', 0, false, false);

        // Act
        $isValid = $fileInfo->isValid();

        // Assert
        $this->assertFalse($isValid);
    }

    public function testIsValidReturnsFalseWhenNotReadable(): void
    {
        // Arrange
        $fileInfo = new FileInfo('/path/to/file.txt', 1024, true, false);

        // Act
        $isValid = $fileInfo->isValid();

        // Assert
        $this->assertFalse($isValid);
    }

    public function testFromFilePathWithExistingFile(): void
    {
        // Act
        $fileInfo = FileInfo::fromFilePath($this->tempFile);

        // Assert
        $this->assertEquals($this->tempFile, $fileInfo->filePath);
        $this->assertTrue($fileInfo->exists);
        $this->assertTrue($fileInfo->readable);
        $this->assertGreaterThan(0, $fileInfo->sizeBytes);
        $this->assertTrue($fileInfo->isValid());
    }

    public function testFromFilePathWithNonExistentFile(): void
    {
        // Arrange
        $nonExistentFile = '/path/to/non/existent/file.txt';

        // Act
        $fileInfo = FileInfo::fromFilePath($nonExistentFile);

        // Assert
        $this->assertEquals($nonExistentFile, $fileInfo->filePath);
        $this->assertFalse($fileInfo->exists);
        $this->assertFalse($fileInfo->readable);
        $this->assertEquals(0, $fileInfo->sizeBytes);
        $this->assertFalse($fileInfo->isValid());
    }
}
