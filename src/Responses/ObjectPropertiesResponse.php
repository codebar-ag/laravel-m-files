<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use Saloon\Http\Response;

final class ObjectPropertiesResponse
{
    public static function createDtoFromResponse(Response $response): ObjectProperties
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        return ObjectProperties::fromArray($response->json());
    }
}
