<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Tests\Unit\DTO;

use Carbon\CarbonImmutable;
use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Tests\TestCase;
use Illuminate\Support\Collection;

class ObjectPropertiesTest extends TestCase
{
    public function test_constructor_creates_object_properties_with_correct_properties(): void
    {
        $classId = 1;
        $objectId = 123;
        $objectTypeId = 0;
        $objectVersionId = 1;
        $lastModifiedAt = CarbonImmutable::parse('2023-01-01T10:00:00Z');
        $properties = collect([]);
        $files = collect([]);

        $objectProperties = new ObjectProperties(
            $classId,
            $objectId,
            $objectTypeId,
            $objectVersionId,
            $lastModifiedAt,
            $properties,
            $files
        );

        $this->assertEquals($classId, $objectProperties->classId);
        $this->assertEquals($objectId, $objectProperties->objectId);
        $this->assertEquals($objectTypeId, $objectProperties->objectTypeId);
        $this->assertEquals($objectVersionId, $objectProperties->objectVersionId);
        $this->assertEquals($lastModifiedAt, $objectProperties->lastModifiedAt);
        $this->assertEquals($properties, $objectProperties->properties);
        $this->assertEquals($files, $objectProperties->files);
    }

    public function test_from_array_creates_object_properties_with_all_properties(): void
    {
        $data = [
            'Class' => 2,
            'ObjVer.ID' => 456,
            'ObjVer.Type' => 1,
            'ObjVer.Version' => 2,
            'ObjVer.Modified' => '2023-01-02T11:00:00Z',
            'Properties' => [
                [
                    'PropertyDef' => 1,
                    'Value' => [
                        'DataType' => 1,
                        'Value' => 'Test Property',
                        'DisplayValue' => 'Test Display',
                    ],
                ],
            ],
            'Files' => [
                [
                    'ID' => 789,
                    'Name' => 'test-file.pdf',
                    'Extension' => 'pdf',
                    'Version' => 1,
                    'Size' => 1024,
                ],
            ],
        ];

        $objectProperties = ObjectProperties::fromArray($data);

        $this->assertEquals(2, $objectProperties->classId);
        $this->assertEquals(456, $objectProperties->objectId);
        $this->assertEquals(1, $objectProperties->objectTypeId);
        $this->assertEquals(2, $objectProperties->objectVersionId);
        $this->assertInstanceOf(CarbonImmutable::class, $objectProperties->lastModifiedAt);
        $this->assertEquals('2023-01-02T11:00:00.000000Z', $objectProperties->lastModifiedAt->toISOString());
        $this->assertInstanceOf(Collection::class, $objectProperties->properties);
        $this->assertEquals(1, $objectProperties->properties->count());
        $this->assertInstanceOf(Collection::class, $objectProperties->files);
        $this->assertEquals(1, $objectProperties->files->count());
    }

    public function test_from_array_creates_object_properties_with_empty_collections(): void
    {
        $data = [
            'Class' => 3,
            'ObjVer.ID' => 789,
            'ObjVer.Type' => 0,
            'ObjVer.Version' => 1,
            'ObjVer.Modified' => '2023-01-03T12:00:00Z',
            'Properties' => [],
            'Files' => [],
        ];

        $objectProperties = ObjectProperties::fromArray($data);

        $this->assertEquals(3, $objectProperties->classId);
        $this->assertEquals(789, $objectProperties->objectId);
        $this->assertEquals(0, $objectProperties->objectTypeId);
        $this->assertEquals(1, $objectProperties->objectVersionId);
        $this->assertInstanceOf(CarbonImmutable::class, $objectProperties->lastModifiedAt);
        $this->assertEquals('2023-01-03T12:00:00.000000Z', $objectProperties->lastModifiedAt->toISOString());
        $this->assertInstanceOf(Collection::class, $objectProperties->properties);
        $this->assertEquals(0, $objectProperties->properties->count());
        $this->assertInstanceOf(Collection::class, $objectProperties->files);
        $this->assertEquals(0, $objectProperties->files->count());
    }

    public function test_from_array_creates_object_properties_with_multiple_properties_and_files(): void
    {
        $data = [
            'Class' => 4,
            'ObjVer.ID' => 999,
            'ObjVer.Type' => 1,
            'ObjVer.Version' => 3,
            'ObjVer.Modified' => '2023-01-04T13:00:00Z',
            'Properties' => [
                [
                    'PropertyDef' => 1,
                    'Value' => [
                        'DataType' => 1,
                        'Value' => 'Property 1',
                        'DisplayValue' => 'Display 1',
                    ],
                ],
                [
                    'PropertyDef' => 2,
                    'Value' => [
                        'DataType' => 2,
                        'Value' => 'Property 2',
                        'DisplayValue' => 'Display 2',
                    ],
                ],
            ],
            'Files' => [
                [
                    'ID' => 111,
                    'Name' => 'file1.pdf',
                    'Extension' => 'pdf',
                    'Version' => 1,
                    'Size' => 512,
                ],
                [
                    'ID' => 222,
                    'Name' => 'file2.docx',
                    'Extension' => 'docx',
                    'Version' => 1,
                    'Size' => 1024,
                ],
            ],
        ];

        $objectProperties = ObjectProperties::fromArray($data);

        $this->assertEquals(4, $objectProperties->classId);
        $this->assertEquals(999, $objectProperties->objectId);
        $this->assertEquals(1, $objectProperties->objectTypeId);
        $this->assertEquals(3, $objectProperties->objectVersionId);
        $this->assertInstanceOf(CarbonImmutable::class, $objectProperties->lastModifiedAt);
        $this->assertEquals('2023-01-04T13:00:00.000000Z', $objectProperties->lastModifiedAt->toISOString());
        $this->assertInstanceOf(Collection::class, $objectProperties->properties);
        $this->assertEquals(2, $objectProperties->properties->count());
        $this->assertInstanceOf(Collection::class, $objectProperties->files);
        $this->assertEquals(2, $objectProperties->files->count());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $lastModifiedAt = CarbonImmutable::parse('2023-01-01T10:00:00Z');
        $properties = collect([]);
        $files = collect([]);

        $objectProperties = new ObjectProperties(
            1,
            123,
            0,
            1,
            $lastModifiedAt,
            $properties,
            $files
        );

        $array = $objectProperties->toArray();

        $expected = [
            'classId' => 1,
            'objectId' => 123,
            'objectTypeId' => 0,
            'objectVersionId' => 1,
            'lastModifiedAt' => '2023-01-01T10:00:00+00:00',
            'properties' => [],
            'files' => [],
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_to_array_returns_correct_structure_with_collections(): void
    {
        $lastModifiedAt = CarbonImmutable::parse('2023-01-01T10:00:00Z');
        $properties = collect([
            [
                'PropertyDef' => 1,
                'Value' => 'Test Property',
                'DisplayValue' => 'Test Display',
            ],
        ]);
        $files = collect([
            [
                'id' => 456,
                'name' => 'test.pdf',
                'extension' => 'pdf',
                'version' => 1,
                'size' => 1024,
            ],
        ]);

        $objectProperties = new ObjectProperties(
            2,
            456,
            1,
            2,
            $lastModifiedAt,
            $properties,
            $files
        );

        $array = $objectProperties->toArray();

        $this->assertEquals(2, $array['classId']);
        $this->assertEquals(456, $array['objectId']);
        $this->assertEquals(1, $array['objectTypeId']);
        $this->assertEquals(2, $array['objectVersionId']);
        $this->assertEquals('2023-01-01T10:00:00+00:00', $array['lastModifiedAt']);
        $this->assertIsArray($array['properties']);
        $this->assertIsArray($array['files']);
        $this->assertEquals(1, count($array['properties']));
        $this->assertEquals(1, count($array['files']));
    }

    public function test_properties_are_readonly(): void
    {
        $lastModifiedAt = CarbonImmutable::parse('2023-01-01T10:00:00Z');
        $properties = collect([]);
        $files = collect([]);

        $objectProperties = new ObjectProperties(
            1,
            123,
            0,
            1,
            $lastModifiedAt,
            $properties,
            $files
        );

        $this->expectException(\Error::class);
        $objectProperties->classId = 999;
    }
}
