<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class GetProperty
{
    public function __construct(
        public readonly int $propertyDef,
        public readonly MFDataTypeEnum $dataType,
        public readonly mixed $value,
        public readonly mixed $displayValue,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dataType = self::resolveDataType(Arr::get($data, 'Value.DataType'));

        $values = self::getValues($dataType, Arr::get($data, 'Value'));

        return new self(
            propertyDef: self::resolvePropertyDef(Arr::get($data, 'PropertyDef')),
            dataType: $dataType,
            value: Arr::get($values, 'Value'),
            displayValue: Arr::get($values, 'DisplayValue'),
        );
    }

    /**
     * Map the numeric M-Files data type onto the enum.
     *
     * MFDataTypeEnum covers the documented types, but a vault can return one that is
     * not in it (4 and 15+ are unmapped). tryFrom() then returned null, which was
     * passed straight into a non-nullable MFDataTypeEnum parameter and killed the
     * whole response with a TypeError. An unknown type now degrades to TEXT so the
     * raw Value/DisplayValue still reach the caller.
     */
    private static function resolveDataType(mixed $value): MFDataTypeEnum
    {
        $numeric = match (true) {
            is_int($value) => $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => null,
        };

        if ($numeric === null) {
            return MFDataTypeEnum::TEXT;
        }

        return MFDataTypeEnum::tryFrom($numeric) ?? MFDataTypeEnum::TEXT;
    }

    private static function resolvePropertyDef(mixed $value): int
    {
        return match (true) {
            is_int($value) => $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => throw new InvalidArgumentException(
                'M-Files property payload is missing the required integer field [PropertyDef].'
            ),
        };
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

    /**
     * @return array<string, mixed>
     */
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
