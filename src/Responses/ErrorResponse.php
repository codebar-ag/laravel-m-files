<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\MFilesError;
use Saloon\Http\Response;

final class ErrorResponse
{
    public static function createDtoFromResponse(Response $response): MFilesError
    {
        return MFilesError::fromArray($response->json());
    }
}
