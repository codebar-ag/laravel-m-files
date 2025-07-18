<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class Documents
{
    public function __construct(
        public readonly Collection $items,
        public readonly ?int $totalCount = null,
        public readonly ?int $page = null,
        public readonly ?int $pageSize = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $items = collect(Arr::get($data, 'Items', []))
            ->map(fn (array $item) => Document::fromArray($item));

        return new self(
            items: $items,
            totalCount: Arr::get($data, 'TotalCount'),
            page: Arr::get($data, 'Page'),
            pageSize: Arr::get($data, 'PageSize'),
        );
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items->map(fn (Document $document) => $document->toArray())->toArray(),
            'totalCount' => $this->totalCount,
            'page' => $this->page,
            'pageSize' => $this->pageSize,
        ];
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }

    public function first(): ?Document
    {
        return $this->items->first();
    }

    public function last(): ?Document
    {
        return $this->items->last();
    }
}
