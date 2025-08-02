<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\ObjectProperties;
use Saloon\Http\Response;

final class ObjectPropertiesResponse
{
    public static function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectProperties::fromArray($response->json());
    }
}
