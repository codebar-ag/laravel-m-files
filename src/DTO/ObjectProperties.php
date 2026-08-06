<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class ObjectProperties
{
    /**
     * @param  Collection<int, GetProperty>  $properties
     * @param  Collection<int, File>  $files
     */
    public function __construct(
        public readonly int $classId,
        public readonly int $objectId,
        public readonly int $objectTypeId,
        public readonly int $objectVersionId,
        public readonly CarbonImmutable $lastModifiedAt,
        public readonly Collection $properties,
        public readonly Collection $files,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $lastModifiedAt = Arr::get($data, 'ObjVer.Modified');
        $propertiesRaw = Arr::get($data, 'Properties', []);
        $filesRaw = Arr::get($data, 'Files', []);
        if (! is_array($propertiesRaw)) {
            $propertiesRaw = [];
        }
        if (! is_array($filesRaw)) {
            $filesRaw = [];
        }
        /** @var list<array<string, mixed>> $propertyList */
        $propertyList = array_values($propertiesRaw);
        /** @var list<array<string, mixed>> $fileList */
        $fileList = array_values($filesRaw);

        return new self(
            classId: Arr::get($data, 'Class'),
            objectId: Arr::get($data, 'ObjVer.ID'),
            objectTypeId: Arr::get($data, 'ObjVer.Type'),
            objectVersionId: Arr::get($data, 'ObjVer.Version'),
            lastModifiedAt: CarbonImmutable::parse($lastModifiedAt),
            properties: collect($propertyList)->map(fn (array $property) => GetProperty::fromArray($property)),
            files: collect($fileList)->map(fn (array $file) => File::fromArray($file)),
        );
    }

    /**
     * @return array<string, mixed>
     */
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
