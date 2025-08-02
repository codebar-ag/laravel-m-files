<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use Illuminate\Support\Arr;

final class GetProperty
{
    public function __construct(
        public readonly int $propertyDef,
        public readonly MFDataTypeEnum $dataType,
        public readonly mixed $value,
        public readonly mixed $displayValue,
    ) {}

    public static function fromArray(array $data): self
    {
        $dataTypeValue = Arr::get($data, 'Value.DataType');
        $dataType = $dataTypeValue !== null ? MFDataTypeEnum::tryFrom($dataTypeValue) : MFDataTypeEnum::TEXT;

        $values = self::getValues($dataType, Arr::get($data, 'Value'));

        return new self(
            propertyDef: Arr::get($data, 'PropertyDef'),
            dataType: $dataType,
            value: Arr::get($values, 'Value'),
            displayValue: Arr::get($values, 'DisplayValue'),
        );
    }

    private static function getValues(MFDataTypeEnum $dataType, mixed $value): mixed
    {
        return match ($dataType) {
            MFDataTypeEnum::LOOKUP => [
                'Value' => Arr::get($value, 'Lookup'),
                'DisplayValue' => Arr::get($value, 'DisplayValue'),
            ],
            MFDataTypeEnum::MULTISELECTLOOKUP => [
                'Value' => Arr::get($value, 'Lookups'),
                'DisplayValue' => Arr::get($value, 'DisplayValue'),
            ],
            default => [
                'Value' => Arr::get($value, 'Value'),
                'DisplayValue' => Arr::get($value, 'DisplayValue'),
            ],
        };
    }

    public function toArray(): array
    {
        return [
            'propertyDef' => $this->propertyDef,
            'dataType' => $this->dataType->value,
            'value' => $this->value,
            'displayValue' => $this->displayValue,
        ];
    }
}
