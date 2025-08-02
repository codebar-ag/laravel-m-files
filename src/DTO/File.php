<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class File
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $extension,
        public readonly ?int $version,
        public readonly ?int $size
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'ID'),
            name: Arr::get($data, 'Name'),
            extension: Arr::get($data, 'Extension'),
            version: Arr::get($data, 'Version'),
            size: Arr::get($data, 'Size')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'extension' => $this->extension,
            'version' => $this->version,
            'size' => $this->size,
        ];
    }
}
