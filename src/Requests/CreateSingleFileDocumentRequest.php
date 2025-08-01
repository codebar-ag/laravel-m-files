<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Responses\CreateSingleFileDocumentResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CreateSingleFileDocumentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public string $title,
        public array $files = [],
        public array $propertyValues = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/objects/0';
    }

    protected function defaultBody(): array
    {
        return [
            'PropertyValues' => collect($this->propertyValues)
                ->map(fn (PropertyValue $propertyValue) => $propertyValue->toArray())
                ->toArray(),
            'Files' => $this->files,
        ];
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): Document
    {
        return CreateSingleFileDocumentResponse::createDtoFromResponse($response);
    }
}
