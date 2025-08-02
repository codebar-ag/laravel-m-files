<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Responses\ObjectPropertiesResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class GetObjectInformationRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(
        public int $objectType,
        public int $objectId,
        public int $objectVersion,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectType}/{$this->objectId}/{$this->objectVersion}?include=properties";
    }

    public function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectPropertiesResponse::createDtoFromResponse($response);
    }
}
