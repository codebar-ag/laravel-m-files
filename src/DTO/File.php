<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

final class File
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $extension,
        public readonly ?int $size,
        public readonly ?CarbonImmutable $lastModified,
    ) {}

    public static function fromArray(array $data): self
    {
        $lastModified = Arr::get($data, 'LastModified');

        return new self(
            id: Arr::get($data, 'ID'),
            name: Arr::get($data, 'Name'),
            extension: Arr::get($data, 'Extension'),
            size: Arr::get($data, 'Size'),
            lastModified: $lastModified ? CarbonImmutable::parse($lastModified) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'extension' => $this->extension,
            'size' => $this->size,
            'lastModified' => $this->lastModified?->toISOString(),
        ];
    }
}
