<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class DownloadedFile
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $extension,
        public readonly ?int $size,
        public readonly ?string $contentType,
        public readonly string $content,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Arr::get($data, 'name'),
            extension: Arr::get($data, 'extension'),
            size: Arr::get($data, 'size') !== null ? (int) Arr::get($data, 'size') : null,
            contentType: Arr::get($data, 'contentType'),
            content: Arr::get($data, 'content', ''),
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'name' => $this->name,
            'extension' => $this->extension,
            'size' => $this->size,
            'contentType' => $this->contentType,
        ];
    }
}
