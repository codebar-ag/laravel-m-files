<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Helpers;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;

final class TypedPropertyBuilder
{
    public static function buildTypedProperty(int $propertyDefId, MFDataTypeEnum $dataType, mixed $value): array
    {
        return match ($dataType) {
            MFDataTypeEnum::TEXT, MFDataTypeEnum::DATE, MFDataTypeEnum::TIMESTAMP, MFDataTypeEnum::BOOLEAN, MFDataTypeEnum::MULTILINETEXT => [
                'PropertyDef' => $propertyDefId,
                'TypedValue' => [
                    'DataType' => $dataType->value,
                    'Value' => $value,
                ],
            ],
            MFDataTypeEnum::LOOKUP => [
                'PropertyDef' => $propertyDefId,
                'TypedValue' => [
                    'DataType' => MFDataTypeEnum::LOOKUP->value,
                    'Lookup' => [
                        'Item' => $value,
                        'Version' => -1,
                    ],
                ],
            ],
            MFDataTypeEnum::MULTISELECTLOOKUP => [
                'PropertyDef' => $propertyDefId,
                'TypedValue' => [
                    'DataType' => MFDataTypeEnum::MULTISELECTLOOKUP->value,
                    'Lookups' => collect((array) $value)->map(fn (mixed $item) => [
                        'Item' => $item,
                        'Version' => -1,
                    ])->values()->all(),
                ],
            ],
            default => [
                'PropertyDef' => $propertyDefId,
                'TypedValue' => [
                    'DataType' => MFDataTypeEnum::UNINITIALIZED->value,
                    'Value' => $value,
                ],
            ],
        };
    }
}
