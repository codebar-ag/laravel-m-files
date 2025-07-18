<?php

declare(strict_types=1);

use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Helpers\TypedPropertyBuilder;

test('can build text property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(0, MFDataTypeEnum::TEXT, 'Test Title');

    expect($result)->toBe([
        'PropertyDef' => 0,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::TEXT->value,
            'Value' => 'Test Title',
        ],
    ]);
});

test('can build date property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(5, MFDataTypeEnum::DATE, '2024-01-01');

    expect($result)->toBe([
        'PropertyDef' => 5,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::DATE->value,
            'Value' => '2024-01-01',
        ],
    ]);
});

test('can build boolean property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(22, MFDataTypeEnum::BOOLEAN, true);

    expect($result)->toBe([
        'PropertyDef' => 22,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::BOOLEAN->value,
            'Value' => true,
        ],
    ]);
});

test('can build lookup property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(9, MFDataTypeEnum::LOOKUP, 123);

    expect($result)->toBe([
        'PropertyDef' => 9,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::LOOKUP->value,
            'Lookup' => [
                'Item' => 123,
                'Version' => -1,
            ],
        ],
    ]);
});

test('can build multiselect lookup property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(10, MFDataTypeEnum::MULTISELECTLOOKUP, [123, 456]);

    expect($result)->toBe([
        'PropertyDef' => 10,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::MULTISELECTLOOKUP->value,
            'Lookups' => [
                [
                    'Item' => 123,
                    'Version' => -1,
                ],
                [
                    'Item' => 456,
                    'Version' => -1,
                ],
            ],
        ],
    ]);
});

test('can build multiline text property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(13, MFDataTypeEnum::MULTILINETEXT, 'Multiline\nText');

    expect($result)->toBe([
        'PropertyDef' => 13,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::MULTILINETEXT->value,
            'Value' => 'Multiline\nText',
        ],
    ]);
});

test('can build timestamp property with enum', function () {
    $result = TypedPropertyBuilder::buildTypedProperty(7, MFDataTypeEnum::TIMESTAMP, '2024-01-01T10:00:00Z');

    expect($result)->toBe([
        'PropertyDef' => 7,
        'TypedValue' => [
            'DataType' => MFDataTypeEnum::TIMESTAMP->value,
            'Value' => '2024-01-01T10:00:00Z',
        ],
    ]);
});
