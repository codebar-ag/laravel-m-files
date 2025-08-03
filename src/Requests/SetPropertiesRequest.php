<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Responses\ObjectPropertiesResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class SetPropertiesRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::POST;

    public function __construct(
        public int $objectType,
        public int $objectId,
        public int $objectVersion,
        public array $propertyValues,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectType}/{$this->objectId}/{$this->objectVersion}/properties";
    }

    protected function defaultBody(): array
    {
        return collect($this->propertyValues)->map(fn (SetProperty $propertyValue) => $propertyValue->toArray())->toArray();
    }

    public function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectPropertiesResponse::createDtoFromResponse($response);
    }
}
