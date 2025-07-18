<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\Documents;
use CodebarAg\MFiles\Responses\GetDocumentsResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;

class GetDocumentsRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(
        public ?int $page = null,
        public ?int $pageSize = null,
        public ?string $searchString = null,
        public ?int $objectTypeId = null,
        public ?bool $includeDeleted = null,
        public ?bool $includeSubfolders = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/objects';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'q' => $this->searchString,
            'objectType' => $this->objectTypeId,
            'includeDeleted' => $this->includeDeleted ? 'true' : 'false',
            'includeSubfolders' => $this->includeSubfolders ? 'true' : 'false',
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ], fn ($value) => $value !== null);
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): Documents
    {
        return GetDocumentsResponse::createDtoFromResponse($response);
    }
}
