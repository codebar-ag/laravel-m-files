<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\DocumentProperties;
use Saloon\Http\Response;

final class GetDocumentPropertiesResponse
{
    public static function createDtoFromResponse(Response $response): DocumentProperties
    {
        $data = $response->json();

        return DocumentProperties::fromArray($data);
    }
}
