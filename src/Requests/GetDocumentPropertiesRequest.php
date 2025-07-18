<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\DocumentProperties;
use CodebarAg\MFiles\Responses\GetDocumentPropertiesResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;

class GetDocumentPropertiesRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(
        public int $objectId,
        public ?int $objectTypeId = null,
        public ?bool $includeDeleted = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectId}/properties";
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'objectType' => $this->objectTypeId,
            'includeDeleted' => $this->includeDeleted ? 'true' : 'false',
        ], fn ($value) => $value !== null);
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): DocumentProperties
    {
        return GetDocumentPropertiesResponse::createDtoFromResponse($response);
    }
}
