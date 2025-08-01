<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class ObjectProperties
{
    public function __construct(
        public readonly Collection $properties,
        public readonly ?int $objectId = null,
        public readonly ?string $objectType = null,
        public readonly ?int $objectTypeId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            properties: collect(Arr::get($data, 'Properties', []))
                ->map(fn (array $property) => Property::fromArray($property)),
            objectId: Arr::get($data, 'ObjectID'),
            objectType: Arr::get($data, 'ObjectType'),
            objectTypeId: Arr::get($data, 'ObjectTypeID'),
        );
    }

    public function toArray(): array
    {
        return [
            'properties' => $this->properties->map(fn (Property $property) => $property->toArray())->toArray(),
            'objectId' => $this->objectId,
            'objectType' => $this->objectType,
            'objectTypeId' => $this->objectTypeId,
        ];
    }
}
