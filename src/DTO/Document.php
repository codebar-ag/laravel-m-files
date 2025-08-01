<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class Document
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $title,
        public readonly ?string $objectType,
        public readonly ?int $objectTypeId,
        public readonly ?string $version,
        public readonly ?CarbonImmutable $created,
        public readonly ?CarbonImmutable $lastModified,
        public readonly ?string $createdBy,
        public readonly ?string $lastModifiedBy,
        public readonly ?bool $isCheckedOut,
        public readonly ?string $checkedOutBy,
        public readonly ?CarbonImmutable $checkedOutAt,
        public readonly ?bool $isDeleted,
        public readonly ?int $propertyID = null,
        public readonly ?Collection $properties = null,
        public readonly ?Collection $files = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $created = Arr::get($data, 'Created');
        $lastModified = Arr::get($data, 'LastModified');
        $checkedOutAt = Arr::get($data, 'CheckedOutAt');

        $properties = Arr::get($data, 'Properties', []);
        $files = Arr::get($data, 'Files', []);

        $objVerType = Arr::get($data, 'ObjVer.Type');
        $objVerVersion = Arr::get($data, 'ObjVer.Version');

        return new self(
            id: Arr::get($data, 'ObjVer.ID') ?: Arr::get($data, 'ID'),
            title: Arr::get($data, 'Title'),
            objectType: $objVerType && $objVerType != 0 ? (string) $objVerType : Arr::get($data, 'ObjectType'),
            objectTypeId: $objVerType !== null ? $objVerType : Arr::get($data, 'ObjectTypeID'),
            version: $objVerVersion ? (string) $objVerVersion : Arr::get($data, 'Version'),
            created: $created ? CarbonImmutable::parse($created) : null,
            lastModified: $lastModified ? CarbonImmutable::parse($lastModified) : null,
            createdBy: Arr::get($data, 'CreatedBy'),
            lastModifiedBy: Arr::get($data, 'LastModifiedBy'),
            isCheckedOut: Arr::get($data, 'ObjectCheckedOut'),
            checkedOutBy: Arr::get($data, 'CheckedOutBy'),
            checkedOutAt: $checkedOutAt ? CarbonImmutable::parse($checkedOutAt) : null,
            isDeleted: Arr::get($data, 'IsDeleted'),
            propertyID: Arr::get($data, 'propertyID'),
            properties: collect($properties)->map(fn (array $property) => Property::fromArray($property)),
            files: collect($files)->map(fn (array $file) => File::fromArray($file)),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'objectType' => $this->objectType,
            'objectTypeId' => $this->objectTypeId,
            'version' => $this->version,
            'created' => $this->created?->toISOString(),
            'lastModified' => $this->lastModified?->toISOString(),
            'createdBy' => $this->createdBy,
            'lastModifiedBy' => $this->lastModifiedBy,
            'isCheckedOut' => $this->isCheckedOut,
            'checkedOutBy' => $this->checkedOutBy,
            'checkedOutAt' => $this->checkedOutAt?->toISOString(),
            'isDeleted' => $this->isDeleted,
            'propertyID' => $this->propertyID,
            'properties' => $this->properties?->map(fn (Property $property) => $property->toArray())->toArray(),
            'files' => $this->files?->map(fn (File $file) => $file->toArray())->toArray(),
        ];
    }
}
