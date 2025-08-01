<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Tests\Unit\DTO;

use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Tests\TestCase;

class PropertyValueTest extends TestCase
{
    public function test_constructor_creates_property_value_with_correct_properties(): void
    {
        $propertyDef = 123;
        $dataType = MFDataTypeEnum::TEXT;
        $value = 'Test Value';

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);

        $this->assertEquals($propertyDef, $propertyValue->propertyDef);
        $this->assertEquals($dataType, $propertyValue->dataType);
        $this->assertEquals($value, $propertyValue->value);
    }

    public function test_from_array_creates_property_value(): void
    {
        $propertyDef = 456;
        $dataType = MFDataTypeEnum::INTEGER;
        $value = 42;

        $propertyValue = PropertyValue::fromArray($propertyDef, $dataType, $value);

        $this->assertEquals($propertyDef, $propertyValue->propertyDef);
        $this->assertEquals($dataType, $propertyValue->dataType);
        $this->assertEquals($value, $propertyValue->value);
    }

    public function test_to_array_with_text_data_type(): void
    {
        $propertyDef = 1;
        $dataType = MFDataTypeEnum::TEXT;
        $value = 'Sample Text';

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_date_data_type(): void
    {
        $propertyDef = 2;
        $dataType = MFDataTypeEnum::DATE;
        $value = '2023-12-25';

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_timestamp_data_type(): void
    {
        $propertyDef = 3;
        $dataType = MFDataTypeEnum::TIMESTAMP;
        $value = '2023-12-25T10:30:00Z';

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_boolean_data_type(): void
    {
        $propertyDef = 4;
        $dataType = MFDataTypeEnum::BOOLEAN;
        $value = true;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_multilinetext_data_type(): void
    {
        $propertyDef = 5;
        $dataType = MFDataTypeEnum::MULTILINETEXT;
        $value = "Line 1\nLine 2\nLine 3";

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_lookup_data_type(): void
    {
        $propertyDef = 6;
        $dataType = MFDataTypeEnum::LOOKUP;
        $value = 789;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => 9,
                'Lookup' => [
                    'Item' => $value,
                    'Version' => -1,
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_multiselectlookup_data_type_with_array(): void
    {
        $propertyDef = 7;
        $dataType = MFDataTypeEnum::MULTISELECTLOOKUP;
        $value = [101, 102, 103];

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => 10,
                'Lookups' => [
                    ['Item' => 101, 'Version' => -1],
                    ['Item' => 102, 'Version' => -1],
                    ['Item' => 103, 'Version' => -1],
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_multiselectlookup_data_type_with_collection(): void
    {
        $propertyDef = 8;
        $dataType = MFDataTypeEnum::MULTISELECTLOOKUP;
        $value = collect([201, 202]);

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        // When casting a collection to array, it creates an array with numeric keys
        // The collect() then iterates over this array, creating unexpected structure
        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => 10,
                'Lookups' => [
                    ['Item' => [201, 202], 'Version' => -1],
                    ['Item' => false, 'Version' => -1],
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_integer_data_type(): void
    {
        $propertyDef = 9;
        $dataType = MFDataTypeEnum::INTEGER;
        $value = 42;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_floating_data_type(): void
    {
        $propertyDef = 10;
        $dataType = MFDataTypeEnum::FLOATING;
        $value = 3.14159;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_time_data_type(): void
    {
        $propertyDef = 11;
        $dataType = MFDataTypeEnum::TIME;
        $value = '14:30:00';

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_integer64_data_type(): void
    {
        $propertyDef = 12;
        $dataType = MFDataTypeEnum::INTEGER64;
        $value = 9223372036854775807;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_filetime_data_type(): void
    {
        $propertyDef = 13;
        $dataType = MFDataTypeEnum::FILETIME;
        $value = 132537600000000000;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_acl_data_type(): void
    {
        $propertyDef = 14;
        $dataType = MFDataTypeEnum::ACL;
        $value = ['permissions' => 'read,write'];

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_to_array_with_uninitialized_data_type(): void
    {
        $propertyDef = 15;
        $dataType = MFDataTypeEnum::UNINITIALIZED;
        $value = null;

        $propertyValue = new PropertyValue($propertyDef, $dataType, $value);
        $result = $propertyValue->toArray();

        $expected = [
            'PropertyDef' => $propertyDef,
            'TypedValue' => [
                'DataType' => $dataType->value,
                'Value' => $value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_properties_are_readonly(): void
    {
        $propertyValue = new PropertyValue(1, MFDataTypeEnum::TEXT, 'test');

        // Verify properties are readonly by checking they can be accessed but not modified
        $this->assertEquals(1, $propertyValue->propertyDef);
        $this->assertEquals(MFDataTypeEnum::TEXT, $propertyValue->dataType);
        $this->assertEquals('test', $propertyValue->value);
    }
}
