<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\ObjectProperties;
use Saloon\Http\Response;

final class GetObjectInformationResponse
{
    public static function createDtoFromResponse(Response $response): ObjectProperties
    {
        $data = $response->json();

        return ObjectProperties::fromArray($data);
    }
}
