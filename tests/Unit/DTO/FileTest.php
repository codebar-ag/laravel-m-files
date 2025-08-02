<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Tests\Unit\DTO;

use CodebarAg\MFiles\DTO\File;
use CodebarAg\MFiles\Tests\TestCase;

class FileTest extends TestCase
{
    public function test_constructor_creates_file_with_correct_properties(): void
    {
        $id = 123;
        $name = 'test-file.pdf';
        $extension = 'pdf';
        $version = 1;
        $size = 1024;

        $file = new File($id, $name, $extension, $version, $size);

        $this->assertEquals($id, $file->id);
        $this->assertEquals($name, $file->name);
        $this->assertEquals($extension, $file->extension);
        $this->assertEquals($version, $file->version);
        $this->assertEquals($size, $file->size);
    }

    public function test_from_array_creates_file_with_all_properties(): void
    {
        $data = [
            'ID' => 456,
            'Name' => 'document.docx',
            'Extension' => 'docx',
            'Version' => 2,
            'Size' => 2048,
        ];

        $file = File::fromArray($data);

        $this->assertEquals(456, $file->id);
        $this->assertEquals('document.docx', $file->name);
        $this->assertEquals('docx', $file->extension);
        $this->assertEquals(2, $file->version);
        $this->assertEquals(2048, $file->size);
    }

    public function test_from_array_creates_file_with_missing_properties(): void
    {
        $data = [
            'ID' => 789,
            'Name' => 'image.jpg',
        ];

        $file = File::fromArray($data);

        $this->assertEquals(789, $file->id);
        $this->assertEquals('image.jpg', $file->name);
        $this->assertNull($file->extension);
        $this->assertNull($file->version);
        $this->assertNull($file->size);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $file = new File(123, 'test.pdf', 'pdf', 1, 1024);

        $array = $file->toArray();

        $expected = [
            'id' => 123,
            'name' => 'test.pdf',
            'extension' => 'pdf',
            'version' => 1,
            'size' => 1024,
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_properties_are_readonly(): void
    {
        $file = new File(1, 'test.pdf', 'pdf', 1, 1024);

        $this->expectException(\Error::class);
        $file->id = 999;
    }
}
