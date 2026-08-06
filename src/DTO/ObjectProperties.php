<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

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
        $propertiesRaw = Arr::get($data, 'Properties', []);
        $filesRaw = Arr::get($data, 'Files', []);
        if (! is_array($propertiesRaw)) {
            $propertiesRaw = [];
        }
        if (! is_array($filesRaw)) {
            $filesRaw = [];
        }
        /** @var list<array<string, mixed>> $propertyList */
        $propertyList = array_values(array_filter($propertiesRaw, 'is_array'));
        /** @var list<array<string, mixed>> $fileList */
        $fileList = array_values(array_filter($filesRaw, 'is_array'));

        return new self(
            classId: self::requireInt($data, 'Class'),
            objectId: self::requireInt($data, 'ObjVer.ID'),
            objectTypeId: self::requireInt($data, 'ObjVer.Type'),
            objectVersionId: self::requireInt($data, 'ObjVer.Version'),
            lastModifiedAt: self::resolveLastModifiedAt($data),
            properties: collect($propertyList)->map(fn (array $property) => GetProperty::fromArray($property)),
            files: collect($fileList)->map(fn (array $file) => File::fromArray($file)),
        );
    }

    /**
     * Resolve the modification timestamp.
     *
     * `ObjVer` only ever carries {Version, VersionType, ID, Type} — it has no
     * `Modified` key, so reading it always yielded null and CarbonImmutable::parse(null)
     * silently returned "now". Every object therefore reported the time it was fetched
     * as its modification date. The timestamp actually lives at the top level.
     *
     * @param  array<string, mixed>  $data
     */
    private static function resolveLastModifiedAt(array $data): CarbonImmutable
    {
        $candidates = [
            'LastModifiedUtc',
            'LastModified',
            // Retained so any vault that does expose it keeps working.
            'ObjVer.Modified',
            'CreatedUtc',
            'Created',
        ];

        foreach ($candidates as $candidate) {
            $value = Arr::get($data, $candidate);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                return CarbonImmutable::parse($value);
            } catch (Throwable) {
                continue;
            }
        }

        return CarbonImmutable::now();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function requireInt(array $data, string $key): int
    {
        $value = Arr::get($data, $key);

        return match (true) {
            is_int($value) => $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => throw new InvalidArgumentException(
                "M-Files object payload is missing the required integer field [{$key}]."
            ),
        };
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
