<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class ObjectProperties
{
    public function __construct(
        public readonly int $classId,
        public readonly int $objectId,
        public readonly int $objectTypeId,
        public readonly int $objectVersionId,
        public readonly CarbonImmutable $lastModifiedAt,
        public readonly Collection $properties,
        public readonly Collection $files,
    ) {}

    public static function fromArray(array $data): self
    {
        $lastModifiedAt = Arr::get($data, 'ObjVer.Modified');
        $properties = Arr::get($data, 'Properties', []);
        $files = Arr::get($data, 'Files', []);

        return new self(
            classId: Arr::get($data, 'Class'),
            objectId: Arr::get($data, 'ObjVer.ID'),
            objectTypeId: Arr::get($data, 'ObjVer.Type'),
            objectVersionId: Arr::get($data, 'ObjVer.Version'),
            lastModifiedAt: CarbonImmutable::parse($lastModifiedAt),
            properties: collect($properties)->map(fn (array $property) => GetProperty::fromArray($property)),
            files: collect($files)->map(fn (array $file) => File::fromArray($file)),
        );
    }

    public function toArray(): array
    {
        return [
            'classId' => $this->classId,
            'objectId' => $this->objectId,
            'objectTypeId' => $this->objectTypeId,
            'objectVersionId' => $this->objectVersionId,
            'lastModifiedAt' => $this->lastModifiedAt->toIso8601String(),
            'properties' => $this->properties->toArray(),
            'files' => $this->files->toArray(),
        ];
    }
}
