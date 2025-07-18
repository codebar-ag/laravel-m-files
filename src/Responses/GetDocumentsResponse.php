<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\Documents;
use Saloon\Http\Response;

final class GetDocumentsResponse
{
    public static function createDtoFromResponse(Response $response): Documents
    {
        $data = $response->json();

        return Documents::fromArray($data);
    }
}
