<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Tests\Unit\DTO;

use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Tests\TestCase;

class DownloadedFileTest extends TestCase
{
    public function test_constructor_creates_downloaded_file_with_correct_properties(): void
    {
        $name = 'test-file';
        $extension = 'pdf';
        $size = 1024;
        $contentType = 'application/pdf';
        $content = 'file content here';

        $downloadedFile = new DownloadedFile($name, $extension, $size, $contentType, $content);

        $this->assertEquals($name, $downloadedFile->name);
        $this->assertEquals($extension, $downloadedFile->extension);
        $this->assertEquals($size, $downloadedFile->size);
        $this->assertEquals($contentType, $downloadedFile->contentType);
        $this->assertEquals($content, $downloadedFile->content);
    }

    public function test_constructor_creates_downloaded_file_with_null_properties(): void
    {
        $content = 'file content here';

        $downloadedFile = new DownloadedFile(null, null, null, null, $content);

        $this->assertNull($downloadedFile->name);
        $this->assertNull($downloadedFile->extension);
        $this->assertNull($downloadedFile->size);
        $this->assertNull($downloadedFile->contentType);
        $this->assertEquals($content, $downloadedFile->content);
    }

    public function test_from_array_creates_downloaded_file_with_all_properties(): void
    {
        $data = [
            'content' => 'document content',
            'name' => 'document',
            'extension' => 'docx',
            'size' => 2048,
            'contentType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $downloadedFile = DownloadedFile::fromArray($data);

        $this->assertEquals('document content', $downloadedFile->content);
        $this->assertEquals('document', $downloadedFile->name);
        $this->assertEquals('docx', $downloadedFile->extension);
        $this->assertEquals(2048, $downloadedFile->size);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $downloadedFile->contentType);
    }

    public function test_from_array_creates_downloaded_file_with_missing_properties(): void
    {
        $data = [
            'name' => 'image',
            'extension' => 'jpg',
        ];

        $downloadedFile = DownloadedFile::fromArray($data);

        $this->assertEquals('', $downloadedFile->content); // Default value
        $this->assertEquals('image', $downloadedFile->name);
        $this->assertEquals('jpg', $downloadedFile->extension);
        $this->assertNull($downloadedFile->size);
        $this->assertNull($downloadedFile->contentType);
    }

    public function test_from_array_creates_downloaded_file_with_empty_array(): void
    {
        $data = [];

        $downloadedFile = DownloadedFile::fromArray($data);

        $this->assertEquals('', $downloadedFile->content);
        $this->assertNull($downloadedFile->name);
        $this->assertNull($downloadedFile->extension);
        $this->assertNull($downloadedFile->size);
        $this->assertNull($downloadedFile->contentType);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $downloadedFile = new DownloadedFile(
            'test-file',
            'pdf',
            1024,
            'application/pdf',
            'test content'
        );

        $array = $downloadedFile->toArray();

        $expected = [
            'content' => 'test content',
            'name' => 'test-file',
            'extension' => 'pdf',
            'size' => 1024,
            'contentType' => 'application/pdf',
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_to_array_includes_null_properties(): void
    {
        $downloadedFile = new DownloadedFile(null, null, null, null, 'content');

        $array = $downloadedFile->toArray();

        $expected = [
            'content' => 'content',
            'name' => null,
            'extension' => null,
            'size' => null,
            'contentType' => null,
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_properties_are_readonly(): void
    {
        $downloadedFile = new DownloadedFile('test', 'pdf', 1024, 'application/pdf', 'content');

        $this->expectException(\Error::class);
        $downloadedFile->content = 'modified content';
    }
}
