<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use Illuminate\Support\Arr;
use Saloon\Http\Response;

final class LogInToVaultResponse
{
    public static function createDtoFromResponse(Response $response): ?string
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        return Arr::get($response->json(), 'Value');
    }
}
