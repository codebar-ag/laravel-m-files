<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Tests\Unit\DTO;

use CodebarAg\MFiles\DTO\GetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Tests\TestCase;

class GetPropertyTest extends TestCase
{
    public function test_constructor_creates_property_with_correct_properties(): void
    {
        $propertyDef = 123;
        $dataType = MFDataTypeEnum::TEXT;
        $value = 'Test Value';
        $displayValue = 'Test Display Value';

        $getProperty = new GetProperty($propertyDef, $dataType, $value, $displayValue);

        $this->assertEquals($propertyDef, $getProperty->propertyDef);
        $this->assertEquals($dataType, $getProperty->dataType);
        $this->assertEquals($value, $getProperty->value);
        $this->assertEquals($displayValue, $getProperty->displayValue);
    }

    public function test_from_array_creates_property_with_text_data_type(): void
    {
        $data = [
            'PropertyDef' => 1,
            'Value' => [
                'DataType' => MFDataTypeEnum::TEXT->value,
                'Value' => 'Sample Text',
                'DisplayValue' => 'Sample Display Text',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(1, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::TEXT, $getProperty->dataType);
        $this->assertEquals('Sample Text', $getProperty->value);
        $this->assertEquals('Sample Display Text', $getProperty->displayValue);
    }

    public function test_from_array_creates_property_with_lookup_data_type(): void
    {
        $data = [
            'PropertyDef' => 2,
            'Value' => [
                'DataType' => MFDataTypeEnum::LOOKUP->value,
                'Lookup' => 123,
                'DisplayValue' => 'Lookup Display Value',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(2, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::LOOKUP, $getProperty->dataType);
        $this->assertEquals(123, $getProperty->value);
        $this->assertEquals('Lookup Display Value', $getProperty->displayValue);
    }

    public function test_from_array_creates_property_with_multiselectlookup_data_type(): void
    {
        $data = [
            'PropertyDef' => 3,
            'Value' => [
                'DataType' => MFDataTypeEnum::MULTISELECTLOOKUP->value,
                'Lookups' => [456, 789],
                'DisplayValue' => 'Multi Lookup Display Value',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(3, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::MULTISELECTLOOKUP, $getProperty->dataType);
        $this->assertEquals([456, 789], $getProperty->value);
        $this->assertEquals('Multi Lookup Display Value', $getProperty->displayValue);
    }

    public function test_from_array_creates_property_with_date_data_type(): void
    {
        $data = [
            'PropertyDef' => 4,
            'Value' => [
                'DataType' => MFDataTypeEnum::DATE->value,
                'Value' => '2023-12-25',
                'DisplayValue' => 'December 25, 2023',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(4, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::DATE, $getProperty->dataType);
        $this->assertEquals('2023-12-25', $getProperty->value);
        $this->assertEquals('December 25, 2023', $getProperty->displayValue);
    }

    public function test_from_array_creates_property_with_boolean_data_type(): void
    {
        $data = [
            'PropertyDef' => 5,
            'Value' => [
                'DataType' => MFDataTypeEnum::BOOLEAN->value,
                'Value' => true,
                'DisplayValue' => 'Yes',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(5, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::BOOLEAN, $getProperty->dataType);
        $this->assertEquals(true, $getProperty->value);
        $this->assertEquals('Yes', $getProperty->displayValue);
    }

    public function test_from_array_handles_missing_data_type(): void
    {
        $data = [
            'PropertyDef' => 6,
            'Value' => [
                'Value' => 'Default Value',
                'DisplayValue' => 'Default Display Value',
            ],
        ];

        $getProperty = GetProperty::fromArray($data);

        $this->assertEquals(6, $getProperty->propertyDef);
        $this->assertEquals(MFDataTypeEnum::TEXT, $getProperty->dataType);
        $this->assertEquals('Default Value', $getProperty->value);
        $this->assertEquals('Default Display Value', $getProperty->displayValue);
    }
}
