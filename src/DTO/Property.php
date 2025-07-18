<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use Illuminate\Support\Arr;

final class Property
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?MFDataTypeEnum $dataType,
        public readonly ?int $dataTypeId,
        public readonly mixed $value,
        public readonly ?bool $hasValue,
        public readonly ?string $displayValue,
        public readonly ?array $lookupValues = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $dataTypeId = Arr::get($data, 'DataType');
        $dataType = $dataTypeId ? MFDataTypeEnum::tryFrom($dataTypeId) : null;

        return new self(
            id: Arr::get($data, 'PropertyDef'),
            name: Arr::get($data, 'Name'),
            dataType: $dataType,
            dataTypeId: $dataTypeId,
            value: Arr::get($data, 'Value'),
            hasValue: Arr::get($data, 'HasValue'),
            displayValue: Arr::get($data, 'DisplayValue'),
            lookupValues: Arr::get($data, 'LookupValues'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'dataType' => $this->dataType?->value,
            'dataTypeId' => $this->dataTypeId,
            'value' => $this->value,
            'hasValue' => $this->hasValue,
            'displayValue' => $this->displayValue,
            'lookupValues' => $this->lookupValues,
        ];
    }
}
