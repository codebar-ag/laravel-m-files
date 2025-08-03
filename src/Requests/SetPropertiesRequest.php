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

class SetPropertiesRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public int $objectType,
        public int $objectId,
        public int $objectVersion,
        public array $propertyValues,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectType}/{$this->objectId}/latest/properties";
    }

    protected function defaultBody(): array
    {
        $body = collect($this->propertyValues)->map(fn (SetProperty $propertyValue) => $propertyValue->toArray())->toArray();

        return $body;
    }

    public function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectPropertiesResponse::createDtoFromResponse($response);
    }
}
