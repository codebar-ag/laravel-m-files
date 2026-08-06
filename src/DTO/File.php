<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;
use InvalidArgumentException;

final class File
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $extension,
        public readonly ?int $version,
        public readonly ?int $size
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $id = self::toInt(Arr::get($data, 'ID'));

        if ($id === null) {
            throw new InvalidArgumentException('M-Files file payload is missing the required integer field [ID].');
        }

        return new self(
            id: $id,
            name: self::toString(Arr::get($data, 'Name')) ?? '',
            extension: self::toString(Arr::get($data, 'Extension')),
            version: self::toInt(Arr::get($data, 'Version')),
            size: self::toInt(Arr::get($data, 'Size')),
        );
    }

    private static function toInt(mixed $value): ?int
    {
        return match (true) {
            is_int($value) => $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => null,
        };
    }

    private static function toString(mixed $value): ?string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
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
