<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;

final class SetProperty
{
    public function __construct(
        public readonly int $propertyDef,
        public readonly MFDataTypeEnum $dataType,
        public readonly mixed $value,
        public readonly mixed $displayValue = null,
    ) {}

    public static function fromArray(int $propertyDef, MFDataTypeEnum $dataType, mixed $value, mixed $displayValue = null): self
    {
        return new self(
            propertyDef: $propertyDef,
            dataType: $dataType,
            value: $value,
            displayValue: $displayValue ?? $value,
        );
    }

    public function toArray(): array
    {
        return match ($this->dataType) {
            MFDataTypeEnum::TEXT,  MFDataTypeEnum::DATE,  MFDataTypeEnum::TIMESTAMP,  MFDataTypeEnum::BOOLEAN,  MFDataTypeEnum::MULTILINETEXT => [
                'PropertyDef' => $this->propertyDef,
                'TypedValue' => [
                    'DataType' => $this->dataType->value,
                    'Value' => $this->value,
                ],
            ],
            MFDataTypeEnum::LOOKUP => [
                'PropertyDef' => $this->propertyDef,
                'TypedValue' => [
                    'DataType' => 9,
                    'Lookup' => [
                        'Item' => $this->value,
                        'Version' => -1,
                    ],
                ],
            ],
            MFDataTypeEnum::MULTISELECTLOOKUP => [
                'PropertyDef' => $this->propertyDef,
                'TypedValue' => [
                    'DataType' => 10,
                    'Lookups' => collect($this->value)->map(fn (mixed $item) => [
                        'Item' => $item,
                        'Version' => -1,
                    ])->values()->all(),
                ],
            ],
            default => [
                'PropertyDef' => $this->propertyDef,
                'TypedValue' => [
                    'DataType' => $this->dataType->value,
                    'Value' => $this->value,
                ],
            ],
        };
    }
}
