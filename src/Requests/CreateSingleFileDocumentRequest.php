<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Responses\ObjectPropertiesResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
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
                ->map(fn (SetProperty $propertyValue) => $propertyValue->toArray())
                ->values()
                ->toArray(),
            'Files' => [$this->files],
        ];
    }

    public function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectPropertiesResponse::createDtoFromResponse($response);
    }
}
