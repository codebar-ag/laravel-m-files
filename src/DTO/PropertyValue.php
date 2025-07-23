<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use Illuminate\Support\Arr;

final class PropertyValue
{
    public function __construct(
        public readonly int $propertyDef,
        public readonly MFDataTypeEnum $dataType,
        public readonly mixed $value,
        public readonly ?array $lookup = null,
        public readonly ?array $lookups = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $typedValue = Arr::get($data, 'TypedValue', []);
        $dataTypeId = Arr::get($typedValue, 'DataType', 0);
        $dataType = MFDataTypeEnum::tryFrom($dataTypeId) ?? MFDataTypeEnum::UNINITIALIZED;

        return new self(
            propertyDef: Arr::get($data, 'PropertyDef'),
            dataType: $dataType,
            value: Arr::get($typedValue, 'Value'),
            lookup: Arr::get($typedValue, 'Lookup'),
            lookups: Arr::get($typedValue, 'Lookups'),
        );
    }

    public function toArray(): array
    {
        $typedValue = [
            'DataType' => $this->dataType->value,
        ];

        if ($this->lookup !== null) {
            $typedValue['Lookup'] = $this->lookup;
        } elseif ($this->lookups !== null) {
            $typedValue['Lookups'] = $this->lookups;
        } else {
            $typedValue['Value'] = $this->value;
        }

        return [
            'PropertyDef' => $this->propertyDef,
            'TypedValue' => $typedValue,
        ];
    }
}
