<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class DownloadedFile
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $name,
        public readonly ?string $extension,
        public readonly ?int $size,
        public readonly ?string $contentType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: Arr::get($data, 'content', ''),
            name: Arr::get($data, 'name'),
            extension: Arr::get($data, 'extension'),
            size: Arr::get($data, 'size'),
            contentType: Arr::get($data, 'contentType'),
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
